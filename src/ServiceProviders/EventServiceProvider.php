<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use BetterWpHooks\Contracts\Dispatcher;
    use Illuminate\Support\Facades\Http;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\DoShutdown;
    use WPEmerge\Events\FilterWpQuery;
    use WPEmerge\Events\MakingView;
    use WPEmerge\Events\UnrecoverableExceptionHandled;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\LoadedWpAdmin;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Events\AdminBodySendable;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\BodySent;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\View\ViewService;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'template_include' => ['resolve', IncomingWebRequest::class, 3001],
            'admin_init' => ['resolve', LoadedWpAdmin::class, 3001],
            'request' => ['resolve', FilterWpQuery::class, 3001],

        ];

        private $event_listeners = [

            IncomingWebRequest::class => [

                HttpKernel::class.'@handle',

            ],

            IncomingAdminRequest::class => [

                HttpKernel::class.'@handle',

            ],

            IncomingAjaxRequest::class => [

                HttpKernel::class.'@handle',

            ],

            AdminBodySendable::class => [

                HttpKernel::class.'@sendBodyDeferred',

            ],

            BodySent::class => [

                ShutdownHandler::class.'@shutdownWp',

            ],

            UnrecoverableExceptionHandled::class => [

                ShutdownHandler::class.'@exceptionHandled',

            ],

            DoShutdown::class => [

                [ShutdownHandler::class, 'terminate']

            ],

            MakingView::class => [

                [ViewService::class, 'compose'],

            ],

            FilterWpQuery::class => [
                [HttpKernel::class, 'filterRequest']
            ]

        ];

        public function register() : void
        {

            ApplicationEvent::make($this->container)
                            ->map($this->mapped_events)
                            ->listeners($this->event_listeners)
                            ->boot();

            $this->container->instance(Dispatcher::class, ApplicationEvent::dispatcher());


            $this->container->singleton(FilterWpQuery::class, function ($container, $args) {

                return new FilterWpQuery(
                    $this->container->make(ServerRequestInterface::class),
                    ...array_values($args)
                );

            });

        }

        public function bootstrap() : void
        {

            if ( ! $this->config->get('always_run_middleware') ) {

                return;

            }

            ApplicationEvent::listen('init', [HttpKernel::class, 'runGlobalMiddleware'], -999);

        }

    }