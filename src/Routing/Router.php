<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Contracts\ContainerAdapter;
    use WPEmerge\Controllers\FallBackController;
    use WPEmerge\Controllers\ViewController;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Http\ConvertsToResponse;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;
    use WPEmerge\Support\Url;
    use WPEmerge\Traits\GathersMiddleware;
    use WPEmerge\Traits\HoldsRouteBlueprint;
    use WPEmerge\Contracts\ResponseFactory as ResponseFactory;

    /**
     * @mixin RouteDecorator
     */
    class Router
    {

        use GathersMiddleware;
        use HoldsRouteBlueprint;
        use ConvertsToResponse;

        /** @var RouteGroup[] */
        private $group_stack = [];

        /** @var ContainerAdapter */
        private $container;

        /**
         * @var string[]
         */
        private $middleware_groups = [];

        /**
         * @var string[]
         */
        private $middleware_priority = [];

        /**
         * @var string[]
         */
        private $route_middleware_aliases = [];

        /** @var RouteCollection */
        private $routes;

        /**
         * @var bool
         */
        private $with_middleware = true;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        public function __construct(ContainerAdapter $container, RouteCollection $routes, ResponseFactory $response_factory)
        {

            $this->container = $container;
            $this->routes = $routes;
            $this->response_factory = $response_factory;

        }

        public function view(string $url, string $view, array $data = [], int $status = 200, array $headers = []) : Route
        {

            $route = $this->match(['GET', 'HEAD'], $url, ViewController::class.'@handle');
            $route->defaults([
                'view' => $view,
                'data' => $data,
                'status' => $status,
                'headers' => $headers,
            ]);

            return $route;

        }

        public function addRoute(array $methods, string $path, $action = null, $attributes = []) : Route
        {

            $url = $this->applyPrefix($path);

            $route = new Route ($methods, $url, $action);

            if ($this->hasGroupStack()) {

                $this->mergeGroupIntoRoute($route);

            }

            if ( ! empty($attributes)) {

                $this->populateInitialAttributes($route, $attributes);

            }

            return $this->routes->add($route);


        }

        public function group(array $attributes, $routes)
        {

            $this->updateGroupStack(new RouteGroup($attributes));

            $this->registerRoutes($routes);

            $this->deleteLastRouteGroup();

        }

        private function registerRoutes($routes, string $method = null)
        {

            if ($routes instanceof Closure) {

                $routes($this);

            }
            else {

                RouteRegistrar::loadRouteFile($routes);

            }



        }

        public function loadRoutes(string $method = null)
        {

            if ( ! $this->hasGroupStack() ) {


                $this->routes->loadIntoDispatcher($method);

            }


        }

        public function createFallbackWebRoute()
        {

            $this->any('/{path}', [FallBackController::class, 'handle'])->and('path', '.+');
        }



        public function findRoute(Request $request, $wp_query = false) : RouteMatch
        {

            if ($wp_query && $match = $this->currentMatch()) {

                return $match;

            }

            return $this->routes->match($request);

        }

        public function runRoute(Request $request) : Response
        {

            $route_match = $this->findRoute($request);

            if ($route_match->route()) {

                $this->route_match = $route_match;

                return $this->runWithinStack($route_match, $request);

            }

            return $this->response_factory->null();

        }

        public function withoutMiddleware()
        {

            $this->with_middleware = false;

        }

        public function middlewareGroup(string $name, array $middleware) : void
        {

            $this->middleware_groups[$name] = $middleware;

        }

        public function middlewarePriority(array $middleware_priority) : void
        {

            $this->middleware_priority = $middleware_priority;

        }

        public function aliasMiddleware($name, $class) : void
        {

            $this->route_middleware_aliases[$name] = $class;

        }

        private function populateInitialAttributes(Route $route, array $attributes)
        {

            ((new RouteAttributes($route)))->populateInitial($attributes);
        }

        private function deleteLastRouteGroup()
        {

            array_pop($this->group_stack);

        }

        private function updateGroupStack(RouteGroup $group)
        {

            if ($this->hasGroupStack()) {

                $group = $this->mergeWithLastGroup($group);

            }

            $this->group_stack[] = $group;

        }

        private function hasGroupStack() : bool
        {

            return ! empty($this->group_stack);

        }

        private function mergeWithLastGroup(RouteGroup $new_group) : RouteGroup
        {

            return $new_group->mergeWith($this->lastGroup());

        }

        private function runWithinStack(RouteMatch $route_match, Request $request) : Response
        {

            $middleware = [];

            if ($this->with_middleware) {

                $middleware = $route_match->route()->getMiddleware();
                $middleware = $this->mergeGlobalMiddleware($middleware);
                $middleware = $this->expandMiddleware($middleware);
                $middleware = $this->uniqueMiddleware($middleware);
                $middleware = $this->sortMiddleware($middleware);

            }

            /** @var Response $response */
            $response = (new Pipeline($this->container))
                ->send($request)
                ->through($middleware)
                ->then(function ($request) use ($route_match) : Response {

                    $this->container->instance(Request::class, $request);
                    $route_response = $route_match->route()->run($request, $route_match->payload());

                    return $this->response_factory->toResponse($route_response);

                });

            return $response;

        }

        private function applyPrefix(string $url) : string
        {

            if ( ! $this->hasGroupStack() ) {

                // return Url::combinePath('', $url);
                return $url;

            }

            return Url::combinePath($this->lastGroupPrefix(), $url);

        }

        private function mergeGroupIntoRoute(Route $route)
        {

            (new RouteAttributes($route))->mergeGroup($this->lastGroup());

        }

        private function lastGroup()
        {

            return end($this->group_stack);

        }

        private function lastGroupPrefix() : string
        {

            if ( ! $this->hasGroupStack()) {

                return '';

            }

            return $this->lastGroup()->prefix();


        }

        public function __call($method, $parameters)
        {


            if ( ! in_array($method, RouteDecorator::allowed_attributes)) {

                throw new \BadMethodCallException(
                    'Method: '.$method.'does not exists on '.get_class($this)
                );

            }

            if ($method === 'where' || $method === 'middleware') {

                return ((new RouteDecorator($this))->decorate(
                    $method,
                    is_array($parameters[0]) ? $parameters[0] : $parameters)
                );

            }

            return ((new RouteDecorator($this))->decorate($method, $parameters[0]));

        }

        public function currentMatch() : ?RouteMatch
        {

            return $this->routes->currentMatch();

        }

        public function fallback(callable $fallback_handler)
        {
            /** @var FallBackController $controller */
            $controller = $this->container->make(FallBackController::class);
            $controller->setFallbackHandler($fallback_handler);
            $this->container->instance(FallBackController::class, $controller);

        }


    }

