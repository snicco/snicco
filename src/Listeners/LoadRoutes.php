<?php


    declare(strict_types = 1);


    namespace Snicco\Listeners;


    use Snicco\Contracts\RouteRegistrarInterface;
    use Snicco\Events\IncomingApiRequest;
    use Snicco\Events\WpInit;

    class LoadRoutes
    {

        public function __invoke( WpInit $event, RouteRegistrarInterface $registrar )
        {

            $config = $event->config;
            $success = $registrar->loadApiRoutes($config);
            $registrar->loadStandardRoutes($config);
            $registrar->loadIntoRouter();

            if ($success && $event->request->isApiEndPoint()) {

                // This will run as the first hook on init.
                IncomingApiRequest::dispatch([$event->request]);

            }


        }



    }