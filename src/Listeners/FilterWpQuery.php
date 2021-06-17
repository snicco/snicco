<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;

    use BetterWpHooks\Traits\ListensConditionally;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Facade\WP;

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

            if ( ! $match->route() ) {

                return $query_filterable->currentQueryVars();

            }

            $this->removeUnneededFilters();

            return $match->route()->filterWpQuery(
                $query_filterable->currentQueryVars(),
                $match->capturedUrlSegmentValues()
            );


        }


        public function shouldHandle(WpQueryFilterable $event) : bool
        {
           return $event->server_request->isReadVerb();
        }

        private function removeUnneededFilters()
        {
            WP::removeFilter('template_redirect', 'redirect_canonical');
            WP::removeFilter('template_redirect', 'rest_output_link_header');
            WP::removeFilter('template_redirect', 'wp_old_slug_redirect');

        }


    }