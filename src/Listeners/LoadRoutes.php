<?php


    declare(strict_types = 1);


    namespace WPMvc\Listeners;


    use WPMvc\Contracts\RouteRegistrarInterface;
    use WPMvc\Events\IncomingApiRequest;
    use WPMvc\Events\WpInit;

    class LoadRoutes
    {

        public function __invoke( WpInit $event, RouteRegistrarInterface $registrar )
        {

            $config = $event->config;

            $success = $registrar->loadApiRoutes($config);
            $registrar->loadStandardRoutes($config);
            $registrar->loadIntoRouter();

            if ( $success && $event->request->isApiEndPoint() ) {

                // This will run as the first hook on init.
                IncomingApiRequest::dispatch([$event->request]);

            }


        }



    }