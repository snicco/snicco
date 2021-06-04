<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Contracts\Dispatcher;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Listeners\FilterWpQuery;
    use WPEmerge\Session\WpLoginAction;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'template_include' => ['resolve', RoutesLoadable::class, 3001],
            'admin_init' => ['resolve', LoadedWpAdmin::class, 3001],
            'request' => ['resolve', WpQueryFilterable::class, 3001],
            'init' => ['resolve', GlobalRoutesLoadable::class, -999],
            'in_admin_footer' => [StartLoadingAdminFooter::class, 1],
            'wp_login' => ['resolve', WpLoginAction::class],
            'wp_logout' => ['resolve', WpLoginAction::class]

        ];

        private $event_listeners = [


            GlobalRoutesLoadable::class => [

                [ LoadRoutes::class, 'global'],

            ],

            RoutesLoadable::class => [

                [ LoadRoutes::class, 'standard'],

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

            WpLoginAction::class => [

                [HttpKernel::class, 'run']

            ],

            StartLoadingAdminFooter::class => [

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

            $this->container->singleton(RoutesLoadable::class, function ($c, $args) {

                $template_wordpress_loads_for_frontend = is_string(Arr::firstEl($args))
                    ? Arr::firstEl($args)
                    : null;


                return new RoutesLoadable(
                    $c->make(Request::class),
                    $template_wordpress_loads_for_frontend
                );

            });

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