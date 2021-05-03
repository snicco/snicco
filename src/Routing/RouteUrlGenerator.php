<?php


	namespace WPEmerge\Routing;

	use Codeception\Step\Condition;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\Url as UrlUtility;
	use WPEmerge\Support\Str;
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

		// public const pattern = '/(?<required>{[a-z]*})/';
		public const pattern = '/(?<optional>(?:\[\/)?(?<required>{.+?})(?:\]+)?)/';

		public function __construct( Route $route ) {

			$this->route = $route;

		}

		public function to( array $arguments = [] ) : string {

			if ( $condition = $this->hasUrlableCondition() ) {

				return $condition->toUrl( $arguments );

			}

			$url = preg_replace_callback( self::pattern, function ( $matches ) use ( $arguments ) {

				$required = $this->stripBrackets($matches['required']);
				$optional = $this->isOptional($matches['optional']);
				$value    = urlencode( WPEmgereArr::get( $arguments, $required, '' ) );

				if ( $value === '' && ! $optional ) {

					throw new ConfigurationException('Required route segment: {' . $required . '} missing.');

				}

				return ($optional ) ?  '/' . $value : $value;

			}, $this->route->getUrl() );

			return trim(home_url( $url ), '/') . '/';


		}

		private function hasUrlableCondition() : ?UrlableInterface {

			return collect( $this->route->getCompiledConditions() )
				->first( function ( Condition $condition ) {

					return $condition instanceof UrlableInterface;

				} );

		}

		private function stripBrackets( string $pattern ) : string {

			$pattern = Str::of($pattern)->between('{', '}')->before(':');

			return $pattern->__toString();

		}

		private function isOptional(string $pattern ) :bool {


			return Str::startsWith($pattern, '[/{') && Str::endsWith($pattern, [']', '}']);

		}

	}