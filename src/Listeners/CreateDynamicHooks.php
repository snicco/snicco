<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;

    use BetterWpHooks\Contracts\Dispatcher;
    use WPEmerge\Events\AdminInit;
    use WPEmerge\Events\IncomingAdminRequest;

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