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
    use WPEmerge\Listeners\ShortCircuit404;
    use WPEmerge\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'admin_init' => [

                [AdminAreaInit::class],

            ],

            'pre_handle_404' => [

               [ Wp404::class, 999 ]

            ],

        ];

        private $ensure_first = [

            'init' => WpInit::class,
            'admin_init' => IncomingAjaxRequest::class,
            'in_admin_footer' => InAdminFooter::class,

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

            Wp404::class => [

                ShortCircuit404::class,

            ],

        ];

        public function register() : void
        {

            $this->bindConfig();
        }

        public function bootstrap() : void
        {

            ApplicationEvent::make($this->container)
                            ->map($this->config->get('events.mapped', []))
                            ->ensureFirst($this->config->get('events.first', []))
                            ->ensureLast($this->config->get('events.last', []))
                            ->listeners($this->config->get('events.listeners', []))
                            ->boot();

            $this->container->instance(Dispatcher::class, ApplicationEvent::dispatcher());

        }

        private function bindConfig()
        {

            $this->config->extend('events.mapped', $this->mapped_events);
            $this->config->extend('events.listeners', $this->event_listeners);
            $this->config->extend('events.first', $this->ensure_first);
            $this->config->extend('events.last', $this->ensure_last);
        }


    }