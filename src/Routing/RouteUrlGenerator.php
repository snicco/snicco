<?php


	namespace WPEmerge\Routing;

	use Codeception\Step\Condition;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\Url as UrlUtility;
	use WPEmerge\Support\WPEmgereArr;

	class RouteUrlGenerator {


		/**
		 * @var \WPEmerge\Routing\Route
		 */
		private $route;

		public const regex_pattern =
			"~
			(?:/)                     	# match leading slash
			(?:\\{)                    	# opening curly brace
			(?<required>[a-z]\\w*) 		# string starting with a-z and followed by word characters for the parameter name
			(?<optional>\\?)?      		# optionally allow the user to mark the parameter as option using a literal ?
			(?:\\})                    	# closing curly brace
			(?=/)                     	# lookahead for a trailing slash  
			~ix";

		public function __construct( Route $route ) {

			$this->route = $route;

		}

		public function to( array $arguments = [] ) : string {

			if ( $condition = $this->hasUrlableCondition() ) {

				return $condition->toUrl( $arguments );

			}

			$url = preg_replace_callback( self::regex_pattern, function ( $matches ) use ( $arguments ) {

				$required = $matches['required'];
				$optional = ! empty( $matches['optional'] );
				$value    = '/' . urlencode( WPEmgereArr::get( $arguments, $required, '' ) );

				if ( $value === '/' ) {

					if ( ! $optional ) {

						throw new ConfigurationException( "Required URL parameter \"$required\" is not specified." );
					}

					$value = '';
				}

				return $value;

			}, $this->route->url() );

			return home_url( UrlUtility::addLeadingSlash( UrlUtility::removeTrailingSlash( $url ) ) );


		}

		private function hasUrlableCondition() : ?UrlableInterface {

			return collect( $this->route->getCompiledConditions() )
				->first( function ( Condition $condition ) {

					return $condition instanceof UrlableInterface;

				} );

		}

	}