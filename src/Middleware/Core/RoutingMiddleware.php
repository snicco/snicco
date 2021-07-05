<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\AbstractRouteCollection;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;

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


