<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Contracts\ContainerAdapter;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Controllers\FallBackController;
    use WPEmerge\Controllers\ViewController;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\ConvertsToResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Routing\Conditions\AdminPageCondition;
    use WPEmerge\Routing\Conditions\IsAdminCondition;
    use WPEmerge\Support\Url;
    use WPEmerge\Traits\GathersMiddleware;
    use WPEmerge\Traits\HoldsRouteBlueprint;
    use WPEmerge\Http\HttpResponseFactory as ResponseFactory;

    /**
     * @mixin RouteDecorator
     */
    class Router
    {

        use HoldsRouteBlueprint;

        /** @var RouteGroup[] */
        private $group_stack = [];

        /** @var ContainerAdapter */
        private $container;

        /** @var AbstractRouteCollection */
        private $routes;


        public function __construct(ContainerAdapter $container, AbstractRouteCollection $routes)
        {

            $this->container = $container;
            $this->routes = $routes;

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

            if ( ! $this->hasGroupStack()) {


                $this->routes->loadIntoDispatcher($method);

            }


        }

        public function createFallbackWebRoute()
        {

            $this->any('/{path}', [FallBackController::class, 'handle'])
                 ->and('path', '.+')
                 ->where(function () {

                     return ! WP::isAdmin();

                 });

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

        public function fallback(callable $fallback_handler)
        {

            /** @var FallBackController $controller */
            $controller = $this->container->make(FallBackController::class);
            $controller->setFallbackHandler($fallback_handler);
            $this->container->instance(FallBackController::class, $controller);

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

        private function applyPrefix(string $url) : string
        {

            if ( ! $this->hasGroupStack()) {

                return $url;

            }

            $url = $this->maybeStripTrailing($url);

            return Url::combineRelativePath($this->lastGroupPrefix(), $url);

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

        private function maybeStripTrailing(string $url) : string
        {

            if (trim($this->lastGroupPrefix(), '/') === WP::wpAdminFolder()) {

                return rtrim($url, '/');

            }

            if (trim($this->lastGroupPrefix(), '/') === WP::ajaxUrl()) {

                return rtrim($url, '/');

            }

            return $url;


        }


    }

