<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\FastRoute;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    class FastRouteUrlGenerator implements RouteUrlGenerator
    {

        /**
         * @var RouteCollection
         */
        private $routes;

        /** @see https://regexr.com/5s536 */
        public const matching_pattern = '/(?<optional>(?:\[\/)?(?<required>{{.+?}}+)(?:\]+)?)/i';

        /** @see https://regexr.com/5s533 */
        public const double_curly_brackets = '/(?<=\/)(?<opening_bracket>\{)|(?<closing_bracket>\}(?=(\/|\[\/|\]|$)))/';

        public function __construct(RouteCollection $routes)
        {

            $this->routes = $routes;
        }

        /**
         * @throws ConfigurationException
         */
        public function to(string $name, array $arguments) : string
        {

            $route = $this->findRoute($name);

            if ( $condition = $this->hasUrleableCondition($route) ) {

                return $condition->toUrl( array_merge([ 'route' => $route ], $arguments) );

            }

            $regex = $this->routeRegex($route);

            $url = (( new FastRouteSyntax() ))->convert($route);

            $url = $this->convertToDoubleCurlyBrackets($url);

            return $this->replaceRouteSegmentsWithValues( $url, $regex, $arguments );

        }


        private function findRoute( string $name ) : Route {

            $route = $this->routes->findByName($name);

            if ( ! $route ) {

                throw new ConfigurationException(
                    'There is no named route with the name: '.$name.' registered.'
                );

            }

            return $route;

        }

        private function hasUrleableCondition( Route $route ) : ?UrlableInterface {

            return collect( $route->getCompiledConditions() )
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

        private function routeRegex(Route $route) : array {

            return Arr::flattenOnePreserveKeys( $route->getRegex() ?? [] );

        }

        private function convertToDoubleCurlyBrackets(string $url) {

            return preg_replace_callback( self::double_curly_brackets, function ( $matches ) {

                if ( $open = $matches['opening_bracket'] ?? null ) {

                    return $open . $open;

                }
                if ( $closing = $matches['closing_bracket'] ?? null ) {

                    return $closing . $closing;

                }


            }, $url );

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