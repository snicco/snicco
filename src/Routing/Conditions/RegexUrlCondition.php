<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Helpers\Url as UrlUtility;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Support\WPEmgereArr;

	class RegexUrlCondition implements ConditionInterface {


		private $url_pattern;

		private $regex;

		private $url_segments;

		/**
		 * Pattern to detect valid parameters in url segments.
		 *
		 * @var string
		 */
		protected $parameter_pattern = '[^/]+';

		public function __construct( string $url_pattern, array $condition ) {

			$this->url_pattern = $url_pattern;
			$this->regex       = [ $condition[0] => $condition[1] ];

		}


		public function isSatisfied( RequestInterface $request ) {

			// $required_segments = UrlParser::requiredSegments( $this->url_pattern );
			//
			// $patterns = collect( $required_segments )->flatMap( function ( $segment ) {
			//
			// 	return [ $segment => $this->regex[ $segment ] ?? null ];
			//
			// } )
			//                                          ->reject( function ( $value ) {
			//
			// 	                                         return ! $value;
			//
			//                                          } )
			//                                          ->all();
			//
			// $url = Url::getPath( $request );
			//
			// $foo = 'bar';

			return $this->whereIsSatisfied($request);


		}

		public function getArguments( RequestInterface $request ) : array {

			return [];

		}

		private function whereIsSatisfied( RequestInterface $request ) {

			$where     = $this->regex;
			$arguments = $this->args( $request );

			foreach ( $where as $parameter => $pattern ) {
				$value = WPEmgereArr::get( $arguments, $parameter, '' );

				if ( ! preg_match( $pattern, $value ) ) {
					return FALSE;
				}
			}

			return TRUE;
		}

		public function args( RequestInterface $request ) {

			$validation_pattern = $this->getValidationPattern( $this->url_pattern );
			$url                = UrlUtility::addTrailingSlash( UrlUtility::getPath( $request ) );
			$matches            = [];
			$success            = preg_match( $validation_pattern, $url, $matches );

			if ( ! $success ) {
				return []; // this should not normally happen
			}

			$arguments       = [];
			$parameter_names = $this->getParameterNames( $this->url_pattern );
			foreach ( $parameter_names as $parameter_name ) {
				$arguments[ $parameter_name ] = isset( $matches[ $parameter_name ] ) ? $matches[ $parameter_name ] : '';
			}

			return $arguments;
		}

		private function getValidationPattern( string $url, $wrap = TRUE ) :string {

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

		protected function replacePatternParameterWithPlaceholder( $matches, &$parameters ) {
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

		protected function getParameterNames( $url ) {
			$matches = [];
			preg_match_all( $this->url_pattern, $url, $matches );

			return $matches['name'];
		}
	}