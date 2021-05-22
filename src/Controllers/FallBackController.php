<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\NullResponse;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;

    class FallBackController
    {

        /**
         * @var ResponseFactory
         */
        private $response;

        /**
         * @var callable
         */
        private $fallback_handler;

        public function __construct(ResponseFactory $response) {

            $this->response = $response;

        }

        public function handle(Request $request, AbstractRouteCollection $routes)
        {

            $possible_routes = collect($routes->withWildCardUrl($request->getMethod()));

            /** @var Route $route */
            $route = $possible_routes->first(function (Route $route) use ($request) {

                $route->instantiateConditions();

                return $route->satisfiedBy($request);

            });

            if ( ! $route ) {

                return ($this->fallback_handler)
                    ? call_user_func($this->fallback_handler, $request)
                    : $this->response->null();

            }

            return $route->instantiateAction()->run($request);

        }

        public function blankResponse() : NullResponse
        {

            return $this->response->queryFiltered();

        }

        public function setFallbackHandler(callable $fallback_handler)
        {
            $this->fallback_handler = $fallback_handler;
        }


    }