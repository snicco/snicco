<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\AbstractRouteCollection;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;

    class RoutingMiddleware extends Middleware
    {

        /**
         * @var AbstractRouteCollection
         */
        private $routes;


        public function __construct(AbstractRouteCollection $routes)
        {
            $this->routes = $routes;
        }


        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            $route_result = $this->routes->match($request);

            return $next(
                $request->withRoutingResult( $route_result)
            );

        }

    }


