<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;

	class RouteUrlGenerator {

		/** @var \WPEmerge\Routing\Route */
		private $route;

		// see: https://regexr.com/5s536
		public const matching_pattern = '/(?<optional>(?:\[\/)?(?<required>{{.+?}}+)(?:\]+)?)/i';

		// see: https://regexr.com/5s533
		public const double_curly_brackets = '/(?<=\/)(?<opening_bracket>\{)|(?<closing_bracket>\}(?=(\/|\[\/|\]|$)))/';


		public function __construct( Route $route ) {

			$this->route = $route;
		}

		/**
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function to( array $arguments = [] ) : string {

			if ( $condition = $this->hasUrleableCondition() ) {

				return $condition->toUrl( $arguments );

			}

			$regex = $this->routeRegex();

			$url = $this->convertToDoubleCurlyBrackets();

			$url = $this->replaceRouteSegmentsWithValues( $url, $regex, $arguments );

			return trim( home_url( $url ), '/' ) . '/';


		}

		private function hasUrleableCondition() : ?UrlableInterface {

			return collect( $this->route->getCompiledConditions() )
				->first( function ( ConditionInterface $condition ) {

					return $condition instanceof UrlableInterface;

				} );

		}

		private function stripBrackets( string $pattern ) : string {

			$pattern = Str::of( $pattern )->between( '{{', '}}' )->before( ':' );

			return $pattern->__toString();

		}

		private function isOptional( string $pattern ) : bool {

			return Str::startsWith( $pattern, '[/{' ) && Str::endsWith( $pattern, [ ']', '}' ] );

		}

		private function satisfiesSegmentRegex( $pattern, $value, $segment ) {

			$regex_constraint = '/' . $pattern . '/';

			if ( ! preg_match( $regex_constraint, $value ) ) {

				throw new ConfigurationException(
					'The provided value [' . $value . '] is not valid for the route: [foo]' .
					PHP_EOL . 'The value for {' . $segment . '} needs to have the regex pattern: ' . $pattern . '.' );


			}

		}

		private function routeRegex() : array {

			return Arr::flattenOnePreserveKeys( $this->route->getRegexConstraints() ?? [] );

		}

		private function convertToDoubleCurlyBrackets() {

			$url = preg_replace_callback( self::double_curly_brackets, function ( $matches ) {

				if ( $open = $matches['opening_bracket'] ?? null ) {

					return $open . $open;

				}
				if ( $closing = $matches['closing_bracket'] ?? null ) {

					return $closing . $closing;

				}


			}, $this->route->getUrl() );

			return $url;

		}

		private function replaceRouteSegmentsWithValues( string $url, array $route_regex, array $values ) {

			return preg_replace_callback( self::matching_pattern, function ( $matches ) use ( $values, $route_regex ) {

				$required = $this->stripBrackets( $matches['required'] );
				$optional = $this->isOptional( $matches['optional'] );
				$value    = Arr::get( $values, $required, '' );

				if ( $value === '' && ! $optional ) {

					throw new ConfigurationException( 'Required route segment: {' . $required . '} missing.' );

				}

				if ( $constraint = Arr::get( $route_regex, $required ) ) {

					$this->satisfiesSegmentRegex( $constraint, $value, $required );

				}

				$encoded = urlencode( $value );

				return ( $optional ) ? '/' . $encoded : $encoded;

			}, $url );

		}

	}