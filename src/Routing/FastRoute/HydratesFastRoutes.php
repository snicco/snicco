<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\FastRoute;

    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Traits\DeserializesRoutes;

    trait HydratesFastRoutes
    {

        use DeserializesRoutes;

        public function hydrateRoutingResult(RoutingResult $routing_result) : RoutingResult
        {

            $route = $routing_result->route();

            if ( $route === null ) {

                return new RoutingResult(null);

            }

            if ( ! $route instanceof Route) {

                $route = $this->hydrateRoute($route);

            }

            $this->unserializeAction($route);

            $this->unserializeWpQueryFilter($route);

            return new RoutingResult($route, $routing_result->capturedUrlSegmentValues());

        }

        public function hydrateRoute(array $route_as_array) : Route
        {

            return Route::hydrate($route_as_array);

        }


    }


