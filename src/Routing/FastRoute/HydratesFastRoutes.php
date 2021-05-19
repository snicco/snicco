<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\FastRoute;

    use FastRoute\Dispatcher;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteMatch;

    trait HydratesFastRoutes
    {

        public function hydrate(array $route_info ) :RouteMatch {

            if ($route_info[0] !== Dispatcher::FOUND)  {

                return new RouteMatch(null, []);

            }

            $route = Route::hydrate($route_info[1]);
            $payload = $route_info[2];

            return new RouteMatch($route, $payload);

        }

    }


