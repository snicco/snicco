<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;

    use BetterWpHooks\Contracts\Dispatcher;
    use WPEmerge\Events\AdminAreaInit;
    use WPEmerge\Events\IncomingAdminRequest;

    class CreateDynamicHooks
    {

        public function __invoke(AdminAreaInit $event, Dispatcher $dispatcher)
        {

            $request = $event->request;
            $hook = $event->hook;

            $dispatcher->listen($hook, function () use ($request) {

                IncomingAdminRequest::dispatch([$request]);

            });

        }


    }