<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Contracts\Dispatcher;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Listeners\CreateDynamicHooks;
    use WPEmerge\Listeners\LoadRoutes;
    use WPEmerge\Listeners\OutputBuffer;
    use WPEmerge\Listeners\FilterWpQuery;
    use WPEmerge\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'admin_init' => [

                [AdminAreaInit::class],

            ],

        ];

        private $ensure_first = [

            'init' => WpInit::class,
            'admin_init' => IncomingAjaxRequest::class,
            'in_admin_footer' => InAdminFooter::class

        ];

        private $ensure_last = [

            'request' => WpQueryFilterable::class,
            'template_include' => IncomingWebRequest::class,

        ];

        private $event_listeners = [

            WpInit::class => [

                LoadRoutes::class,

            ],

            AdminAreaInit::class => [

                CreateDynamicHooks::class,
                [OutputBuffer::class, 'start'],

            ],

            IncomingGlobalRequest::class => [

                [HttpKernel::class, 'run'],

            ],

            IncomingWebRequest::class => [

                [HttpKernel::class, 'run'],

            ],

            IncomingAjaxRequest::class => [

                [HttpKernel::class, 'run'],

            ],

            IncomingAdminRequest::class => [

                [HttpKernel::class, 'run'],

            ],

            WpQueryFilterable::class => [

                [FilterWpQuery::class, 'handle'],

            ],

            InAdminFooter::class => [

                [OutputBuffer::class, 'flush'],

            ],

            UnrecoverableExceptionHandled::class => [

                [ShutdownHandler::class, 'unrecoverableException'],

            ],

            ResponseSent::class => [

                [ShutdownHandler::class, 'handle'],

            ],

            MakingView::class => [

                [ViewFactory::class, 'compose'],

            ],

        ];

        public function register() : void
        {
            //
        }

        public function bootstrap() : void
        {

            ApplicationEvent::make($this->container)
                            ->map($this->mapped_events)
                            ->ensureFirst($this->ensure_first)
                            ->ensureLast($this->ensure_last)
                            ->listeners($this->event_listeners)
                            ->boot();

            $this->container->instance(Dispatcher::class, ApplicationEvent::dispatcher());

        }


    }