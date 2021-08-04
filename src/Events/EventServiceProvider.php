<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use BetterWpHooks\Contracts\Dispatcher;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\ExceptionHandling\ShutdownHandler;
    use Snicco\Http\HttpKernel;
    use Snicco\Listeners\CreateDynamicHooks;
    use Snicco\Listeners\FilterWpQuery;
    use Snicco\Listeners\LoadRoutes;
    use Snicco\Listeners\ShortCircuit404;
    use Snicco\Middleware\Core\OutputBufferMiddleware;
    use Snicco\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'admin_init' => [

                [AdminInit::class],

            ],

            'pre_handle_404' => [

               [ Wp404::class, 999 ]

            ],

            'all_admin_notices' => [

                BeforeAdminFooter::class

            ]

        ];

        private $ensure_first = [

            'init' => WpInit::class,
            'admin_init' => IncomingAjaxRequest::class,

        ];

        private $ensure_last = [

            'do_parse_request' => WpQueryFilterable::class,
            'template_include' => IncomingWebRequest::class,

        ];

        private $event_listeners = [

            WpInit::class => [

                LoadRoutes::class,

            ],

            AdminInit::class => [

                [OutputBufferMiddleware::class, 'start'],
                CreateDynamicHooks::class

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

            IncomingApiRequest::class => [

                [HttpKernel::class, 'run'],

            ],

            WpQueryFilterable::class => [

                [FilterWpQuery::class, 'handleEvent'],

            ],

            UnrecoverableExceptionHandled::class => [

                [ShutdownHandler::class, 'unrecoverableException'],

            ],

            BeforeAdminFooter::class => [

                [OutputBufferMiddleware::class, 'flush']

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

            Event::make($this->container)
                            ->map($this->config->get('events.mapped', []))
                            ->ensureFirst($this->config->get('events.first', []))
                            ->ensureLast($this->config->get('events.last', []))
                            ->listeners($this->config->get('events.listeners', []))
                            ->boot();

            $this->container->instance(Dispatcher::class, Event::dispatcher());

        }

        private function bindConfig()
        {

            $this->config->extend('events.mapped', $this->mapped_events);
            $this->config->extend('events.listeners', $this->event_listeners);
            $this->config->extend('events.first', $this->ensure_first);
            $this->config->extend('events.last', $this->ensure_last);
        }


    }