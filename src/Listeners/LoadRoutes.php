<?php


    declare(strict_types = 1);


    namespace BetterWP\Listeners;


    use BetterWP\Contracts\RouteRegistrarInterface;
    use BetterWP\Events\IncomingApiRequest;
    use BetterWP\Events\WpInit;

    class LoadRoutes
    {

        public function __invoke( WpInit $event, RouteRegistrarInterface $registrar )
        {

            $config = $event->config;

            $success = $registrar->loadApiRoutes($config);
            $registrar->loadStandardRoutes($config);
            $registrar->loadIntoRouter();

            if ( $success && $event->request->isApiEndPoint()  ) {

                // This will run as the first hook on init.
                IncomingApiRequest::dispatch([$event->request]);

            }


        }



    }