<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;

    use BetterWpHooks\Traits\ListensConditionally;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Events\WpQueryFilterable;

    class FilterWpQuery
    {

        use ListensConditionally;

        /**
         * @var AbstractRouteCollection
         */
        private $routes;

        public function __construct(AbstractRouteCollection $routes)
        {
            $this->routes = $routes;
        }

        public function handle (WpQueryFilterable $query_filterable) {

            $request = $query_filterable->server_request;

            $match = $this->routes->matchForQueryFiltering($request);

            if ( $match->route() ) {

                return $match->route()->filterWpQuery(
                    $query_filterable->currentQueryVars(),
                    $match->capturedUrlSegmentValues()
                );

            }

            return $query_filterable->currentQueryVars();

        }


        public function shouldHandle(WpQueryFilterable $event) : bool
        {
           return $event->server_request->isReadVerb();
        }

    }