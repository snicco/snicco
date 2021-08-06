<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;

    class RoutingMiddleware extends Middleware
    {

        private AbstractRouteCollection $routes;

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


