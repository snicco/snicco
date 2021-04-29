<?php

	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\CanFilterQueryInterface;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\Url as UrlUtility;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Support\WPEmgereArr;

	/**
	 * Check against the current url
	 */
	class UrlCondition implements ConditionInterface, UrlableInterface, CanFilterQueryInterface {

		const WILDCARD = '*';


		protected $url = '';

		protected $url_where = [];


		protected $url_pattern = '~
		(?:/)                     # match leading slash
		(?:\{)                    # opening curly brace
			(?P<name>[a-z]\w*)    # string starting with a-z and followed by word characters for the parameter name
			(?P<optional>\?)?     # optionally allow the user to mark the parameter as option using a literal ?
		(?:\})                    # closing curly brace
		(?=/)                     # lookahead for a trailing slash
	~ix';

		/**
		 * Pattern to detect valid parameters in url segments.
		 *
		 * @var string
		 */
		protected $parameter_pattern = '[^/]+';


		public function __construct( $url, $where = [] ) {

			$this->setUrl( UrlParser::normalize( $url ) );
			$this->setUrlWhere( $where );

		}

		protected function make( $url, $where = [] ) {
			return new self( $url, $where );
		}


		protected function whereIsSatisfied( RequestInterface $request ) {

			$where     = $this->getUrlWhere();
			$arguments = $this->getArguments( $request );

			foreach ( $where as $parameter => $pattern ) {
				$value = WPEmgereArr::get( $arguments, $parameter, '' );

				if ( ! preg_match( $pattern, $value ) ) {
					return FALSE;
				}
			}

			return TRUE;
		}


		public function isSatisfied( RequestInterface $request ) {

			if ( $this->getUrl() === static::WILDCARD ) {
				return TRUE;
			}

			$validation_pattern = $this->getValidationPattern( $this->getUrl() );
			$url                = UrlUtility::addTrailingSlash( UrlUtility::getPath( $request ) );
			$match              = (bool) preg_match( $validation_pattern, $url );

			if ( ! $match || empty( $this->getUrlWhere() ) ) {
				return $match;
			}

			return $this->whereIsSatisfied( $request );
		}


		public function getArguments( RequestInterface $request ) {
			$validation_pattern = $this->getValidationPattern( $this->getUrl() );
			$url                = UrlUtility::addTrailingSlash( UrlUtility::getPath( $request ) );
			$matches            = [];
			$success            = preg_match( $validation_pattern, $url, $matches );

			if ( ! $success ) {
				return []; // this should not normally happen
			}

			$arguments       = [];
			$parameter_names = $this->getParameterNames( $this->getUrl() );
			foreach ( $parameter_names as $parameter_name ) {
				$arguments[ $parameter_name ] = isset( $matches[ $parameter_name ] ) ? $matches[ $parameter_name ] : '';
			}

			return $arguments;
		}


		public function getUrl() {
			return $this->url;
		}


		public function setUrl( $url ) {

			if ( $url !== static::WILDCARD ) {
				$url = UrlUtility::addLeadingSlash( UrlUtility::addTrailingSlash( $url ) );
			}

			$this->url = $url;
		}

		public function getUrlWhere() {
			return $this->url_where;
		}

		public function setUrlWhere( $where ) {
			$this->url_where = $where;
		}

		/**
		 * Append a url to this one returning a new instance.
		 *
		 * @param  string                 $url
		 * @param  array<string, string>  $where
		 *
		 * @return static
		 */
		public function concatenate( $url, $where = [] ) {

			if ( $this->getUrl() === static::WILDCARD || $url === static::WILDCARD ) {
				return $this->make( static::WILDCARD );
			}

			$leading  = UrlUtility::addLeadingSlash( UrlUtility::removeTrailingSlash( $this->getUrl() ), TRUE );
			$trailing = UrlUtility::addLeadingSlash( UrlUtility::addTrailingSlash( $url ) );

			return $this->make( $leading . $trailing, array_merge(
				$this->getUrlWhere(),
				$where
			) );
		}

		/**
		 * Get parameter names as defined in the url.
		 *
		 * @param  string  $url
		 *
		 * @return string[]
		 */
		private function getParameterNames( $url ) : array {
			$matches = [];
			preg_match_all( $this->url_pattern, $url, $matches );

			return $matches['name'];
		}

		/**
		 * Validation pattern replace callback.
		 *
		 * @param  array  $matches
		 * @param  array  $parameters
		 *
		 * @return string
		 */
		private function replacePatternParameterWithPlaceholder( $matches, &$parameters ) {
			$name     = $matches['name'];
			$optional = ! empty( $matches['optional'] );

			$replacement = '/(?P<' . $name . '>' . $this->parameter_pattern . ')';

			if ( $optional ) {
				$replacement = '(?:' . $replacement . ')?';
			}

			$hash                       = sha1( implode( '_', [
				count( $parameters ),
				$replacement,
				uniqid( 'wpemerge_', TRUE ),
			] ) );
			$placeholder                = '___placeholder_' . $hash . '___';
			$parameters[ $placeholder ] = $replacement;

			return $placeholder;
		}


		public function getValidationPattern( string $url, $wrap = TRUE ) :string {

			$parameters = [];

			// Replace all parameters with placeholders
			$validation_pattern = preg_replace_callback( $this->url_pattern, function ( $matches ) use ( &$parameters ) {
				return $this->replacePatternParameterWithPlaceholder( $matches, $parameters );
			}, $url );

			// Quote the remaining string so that it does not get evaluated as a pattern.
			$validation_pattern = preg_quote( $validation_pattern, '~' );

			// Replace the placeholders with the real parameter patterns.
			$validation_pattern = str_replace(
				array_keys( $parameters ),
				array_values( $parameters ),
				$validation_pattern
			);

			// Match the entire url; make trailing slash optional.
			$validation_pattern = '^' . $validation_pattern . '?$';

			if ( $wrap ) {
				$validation_pattern = '~' . $validation_pattern . '~';
			}

			return $validation_pattern;
		}


		/** @todo make sure this always returns with / at the end */
		public function toUrl( $arguments = [] ) {

			$url = preg_replace_callback( $this->url_pattern, function ( $matches ) use ( $arguments ) {
				$name     = $matches['name'];
				$optional = ! empty( $matches['optional'] );
				$value    = '/' . urlencode( WPEmgereArr::get( $arguments, $name, '' ) );

				if ( $value === '/' ) {
					if ( ! $optional ) {
						throw new ConfigurationException( "Required URL parameter \"$name\" is not specified." );
					}

					$value = '';
				}

				return $value;
			}, $this->getUrl() );

			return home_url( UrlUtility::addLeadingSlash( UrlUtility::removeTrailingSlash( $url ) ) );
		}

	}
