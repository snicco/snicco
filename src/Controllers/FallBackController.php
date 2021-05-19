<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteCompiler;

    class FallBackController
    {

        /**
         * @var RouteCollection
         */
        private $routes;

        /**
         * @var ResponseFactory
         */
        private $response;
        /**
         * @var ConditionFactory
         */
        private $route_compiler;

        /**
         * @var callable
         */
        private $fallback_handler;

        public function __construct(
            RouteCollection $routes,
            ResponseFactory $response,
            RouteCompiler $route_compiler
        ) {

            $this->routes = $routes;
            $this->response = $response;
            $this->route_compiler = $route_compiler;

        }

        public function handle(Request $request)
        {

            $possible_routes = collect($this->routes->withWildCardUrl($request->getMethod()));

            $routes = $possible_routes->map(function (Route $route) {

                return $this->route_compiler->hydrate((array)$route->compile());

            });

            /** @var CompiledRoute $route */
            $route = $routes->first(function (CompiledRoute $route) use ($request) {

                return $route->satisfiedBy($request);


            });

            if ( ! $route) {

                return ($this->fallback_handler)
                    ? call_user_func($this->fallback_handler, $request)
                    : $this->response->null();

            }

            $payload = [];

            foreach ($route->conditions as $compiled_condition) {

                $args = $compiled_condition->getArguments($request);

                $payload = array_merge($payload, $args);

            }

            return $route->run($request, $payload);

        }

        public function setFallbackHandler(callable $fallback_handler)
        {

            $this->fallback_handler = $fallback_handler;
        }


    }