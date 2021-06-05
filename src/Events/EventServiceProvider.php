<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Contracts\Dispatcher;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Listeners\DynamicHooks;
    use WPEmerge\Listeners\LoadRoutes;
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Listeners\FilterWpQuery;
    use WPEmerge\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'init' => ['resolve', WpInit::class, -999],

            'admin_init' => ['resolve', IncomingAjaxRequest::class, 3001],

            'request' => ['resolve', WpQueryFilterable::class, 3001],

            'template_include' => ['resolve', IncomingWebRequest::class, 3001],

            'in_admin_footer' => [InAdminFooter::class, 1],

        ];

        private $event_listeners = [

            WpInit::class => [

                [ LoadRoutes::class, '__invoke'],
                [ DynamicHooks::class, 'create' ],

            ],

            IncomingGlobalRequest::class => [

                [HttpKernel::class, 'run']

            ],

            IncomingWebRequest::class => [

                [ HttpKernel::class, 'run' ]

            ],

            IncomingAjaxRequest::class => [

                [HttpKernel::class, 'run']

            ],

            IncomingAdminRequest::class => [

                [ HttpKernel::class, 'run']

            ],

            OutputBufferRequired::class => [

                [OutputBufferMiddleware::class, 'start'],

            ],

            WpQueryFilterable::class => [

                [FilterWpQuery::class, 'handle']

            ],

            InAdminFooter::class => [

                [OutputBufferMiddleware::class, 'flush'],

            ],

            UnrecoverableExceptionHandled::class => [

               [ ShutdownHandler::class ,'unrecoverableException' ]

            ],

            ResponseSent::class => [

                [ShutdownHandler::class, 'handle']

            ],

            MakingView::class => [

                [ViewFactory::class, 'compose'],

            ],

        ];

        public function register() : void
        {

            $this->bootDispatcher();

            $this->bindEventObjects();


        }

        public function bootstrap() : void
        {
            //
        }

        private function bootDispatcher()
        {

            ApplicationEvent::make($this->container)
                            ->map($this->mapped_events)
                            ->listeners($this->event_listeners)
                            ->boot();

            $this->container->instance(Dispatcher::class, ApplicationEvent::dispatcher());


        }

        private function bindEventObjects()
        {


            $this->container->singleton(WpQueryFilterable::class, function ($container, $args) {

                return new WpQueryFilterable(
                    $this->container->make(ServerRequestInterface::class),
                    ...array_values($args)
                );

            });

            $this->container->singleton(WpLoginAction::class, function () {

                return new WpLoginAction(

                    $this->container->make(Request::class),
                    $this->config->get('session.enabled', false)

                );

            });


        }

    }