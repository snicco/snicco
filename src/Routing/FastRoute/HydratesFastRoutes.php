<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\FastRoute;

    use FastRoute\Dispatcher;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteResult;
    use WPEmerge\Support\Str;
    use WPEmerge\Traits\DeserializesRoutes;

    trait HydratesFastRoutes
    {

        use DeserializesRoutes;

        public function hydrate(array $route_info ) :RouteResult {

            if ($route_info[0] !== Dispatcher::FOUND)  {

                return new RouteResult(null, []);

            }

            $route = Route::hydrate($route_info[1]);

            $this->unserializeAction($route);

            $payload = $this->normalize($route_info[2]);

            return new RouteResult($route, $payload);

        }

        private function normalize(array $captured_url_segments) :array  {

           return array_map(function ($value) {

                return rtrim($value, '/');

            }, $captured_url_segments);

        }



    }


