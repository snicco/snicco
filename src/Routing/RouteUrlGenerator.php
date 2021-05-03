<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;

	class RouteUrlGenerator {

		/** @var \WPEmerge\Routing\Route  */
		private $route;

		// public const pattern = '/(?<optional>(?:\[\/)?(?<required>{.+?})(?:\]+)?)/i';
		public const pattern = '/(?<optional>(?:\[\/)?(?<required>{.+?}+(?!\w))(?:\]+)?)/i';


		public function __construct( Route $route ) {

			$this->route = $route;

		}

		public function to( array $arguments = [] ) : string {

			if ( $condition = $this->hasUrlableCondition() ) {

				return $condition->toUrl( $arguments );

			}

			$regex = Arr::flattenOnePreserveKeys( $this->route->getRegexConstraints() ?? [] );

			$url = preg_replace_callback( self::pattern, function ( $matches ) use ( $arguments, $regex ) {

				$required = $this->stripBrackets( $matches['required'] );
				$optional = $this->isOptional( $matches['optional'] );
				$value    = Arr::get( $arguments, $required, '' );

				if ( $value === '' && ! $optional ) {

					throw new ConfigurationException( 'Required route segment: {' . $required . '} missing.' );

				}

				if ( $regex = Arr::get( $regex, $required ) ) {

					$this->checkCustomRegex( $regex, $value, $required );

				}

				$encoded = urlencode($value);

				return ( $optional ) ? '/' . $encoded : $encoded;

			}, $this->route->getUrl() );

			return trim( home_url( $url ), '/' ) . '/';


		}

		private function hasUrlableCondition() : ?UrlableInterface {

			return collect( $this->route->getCompiledConditions() )
				->first( function ( ConditionInterface $condition ) {

					return $condition instanceof UrlableInterface;

				} );

		}

		private function stripBrackets( string $pattern ) : string {

			$pattern = Str::of( $pattern )->between( '{', '}' )->before( ':' );

			return $pattern->__toString();

		}

		private function isOptional( string $pattern ) : bool {


			return Str::startsWith( $pattern, '[/{' ) && Str::endsWith( $pattern, [ ']', '}' ] );

		}

		private function checkCustomRegex( $pattern, $value, $segment ) {

			$regex_constraint = '/' . $pattern . '/';

			if ( ! preg_match( $regex_constraint, $value ) ) {

				throw new ConfigurationException(
					'The provided value [bar] is not valid for the route: [foo]' .
					PHP_EOL . 'The value for {' . $segment . '} needs to have the regex pattern: ' . $pattern . '.' );


			}

		}

	}