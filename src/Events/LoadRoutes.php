<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;


    use WPEmerge\Contracts\RouteRegistrarInterface;

    class LoadRoutes
    {


        public function __invoke( RoutesLoadable $event, RouteRegistrarInterface $registrar )
        {

            $config = $event->config;

            $success = $registrar->loadGlobalRoutes($config);

            if ( $success ) {

                IncomingGlobalRequest::dispatch([$event->request]);

            }

            $registrar->loadStandardRoutes($config);


        }



    }