<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;


    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Events\IncomingGlobalRequest;
    use WPEmerge\Events\WpInit;

    class LoadRoutes
    {


        /** @todo With this set up it is not possible to create named routes from matching global routes to standard routes. */
        public function __invoke( WpInit $event, RouteRegistrarInterface $registrar )
        {

            $config = $event->config;

            $success = $registrar->loadGlobalRoutes($config);

            if ( $success ) {

                IncomingGlobalRequest::dispatch([$event->request]);

            }

            $registrar->loadStandardRoutes($config);


        }



    }