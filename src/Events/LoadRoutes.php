<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;


    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Support\Arr;

    class LoadRoutes
    {

        /**
         * @var RouteRegistrar
         */
        private $registrar;

        public function __construct(RouteRegistrar $registrar)
        {
            $this->registrar = $registrar;
        }

        public function global( GlobalRoutesLoadable $event ) {

            $config = $event->config;

            $redirect_routes = Arr::wrap( $config->get('routing.redirects', [] ) );

            if ( $redirect_routes === [] ) {
                return;
            }

            $this->registrar->requireAllFromFiles($redirect_routes);

            IncomingGlobalRequest::dispatch([$event->request]);


        }

        public function standard ( RoutesLoadable $event ) {

            $this->registrar->loadRoutes();

            if ( $event->template ) {

                return IncomingWebRequest::dispatch([$event->template, $event->request]);

            }

            if ( WP::isAdminAjax() ) {

                return IncomingAjaxRequest::dispatch([$event->request]);

            }

            if ( WP::isAdmin() ) {

                return IncomingAdminRequest::dispatch([$event->request]);

            }


        }


    }