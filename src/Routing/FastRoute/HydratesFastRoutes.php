<?php


    declare(strict_types = 1);


    namespace WPMvc\Routing\FastRoute;

    use WPMvc\Routing\Route;
    use WPMvc\Routing\RoutingResult;
    use WPMvc\Traits\DeserializesRoutes;

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


