<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use BetterWpHooks\Contracts\Dispatcher;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\StartLoadingAdminFooter;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Events\LoadedWP;
    use WPEmerge\Events\MakingView;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Events\UnrecoverableExceptionHandled;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\LoadedWpAdmin;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Events\AdminBodySendable;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Routing\FilterWpQuery;
    use WPEmerge\View\ViewService;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'template_include' => ['resolve', IncomingWebRequest::class, 3001],
            'admin_init' => ['resolve', LoadedWpAdmin::class, 3001],
            'request' => ['resolve', WpQueryFilterable::class, 3001],
            'init' => ['resolve', LoadedWP::class, -999],
            'in_admin_footer' => [StartLoadingAdminFooter::class, 1]

        ];

        private $event_listeners = [

            IncomingWebRequest::class => [

                [HttpKernel::class, 'run']

            ],

            IncomingAdminRequest::class => [

                [OutputBufferMiddleware::class, 'start'],

            ],

            StartLoadingAdminFooter::class => [

                [OutputBufferMiddleware::class, 'flush'],

            ],

            IncomingAjaxRequest::class => [

                [HttpKernel::class, 'run']

            ],

            AdminBodySendable::class => [

                [ HttpKernel::class, 'run']

            ],

            UnrecoverableExceptionHandled::class => [

               [ ShutdownHandler::class ,'unrecoverableException' ]

            ],

            ResponseSent::class => [

                [ShutdownHandler::class, 'handle']

            ],

            MakingView::class => [

                [ViewService::class, 'compose'],

            ],

            WpQueryFilterable::class => [
                [FilterWpQuery::class, 'handle']
            ],



        ];

        public function register() : void
        {

            ApplicationEvent::make($this->container)
                            ->map($this->mapped_events)
                            ->listeners($this->event_listeners)
                            ->boot();

            $this->container->instance(Dispatcher::class, ApplicationEvent::dispatcher());


            $this->container->singleton(WpQueryFilterable::class, function ($container, $args) {

                return new WpQueryFilterable(
                    $this->container->make(ServerRequestInterface::class),
                    ...array_values($args)
                );

            });

        }

        public function bootstrap() : void
        {
            //
        }

    }