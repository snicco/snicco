<?php


    declare(strict_types = 1);


    namespace Snicco\Listeners;

    use BetterWpHooks\Traits\ListensConditionally;
    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Events\WpQueryFilterable;
    use Snicco\Routing\Route;

    class FilterWpQuery
    {

        use ListensConditionally;

        private AbstractRouteCollection $routes;

        public function __construct(AbstractRouteCollection $routes)
        {

            $this->routes = $routes;
        }

        public function handleEvent(WpQueryFilterable $event) : bool
        {

            $routing_result = $this->routes->matchForQueryFiltering($event->server_request);

            $route = $routing_result->route();

            if ( ! $route instanceof Route || $route->isFallback()) {

                return $event->do_request;

            }

            $event->wp->query_vars = $route->filterWpQuery(
                $routing_result->capturedUrlSegmentValues()
            );

            return false;


        }

        public function shouldHandle(WpQueryFilterable $event) : bool
        {
           return $event->server_request->isReadVerb();
        }


    }