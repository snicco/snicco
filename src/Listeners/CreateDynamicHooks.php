<?php


    declare(strict_types = 1);


    namespace WPMvc\Listeners;

    use BetterWpHooks\Contracts\Dispatcher;
    use WPMvc\Events\AdminInit;
    use WPMvc\Events\IncomingAdminRequest;

    class CreateDynamicHooks
    {

        public function __invoke(AdminInit $event, Dispatcher $dispatcher)
        {

            $request = $event->request;
            $hook = $event->hook;

            $dispatcher->listen("load-{$hook}", function () use ($request) {

                IncomingAdminRequest::dispatch([$request]);

            });

        }


    }