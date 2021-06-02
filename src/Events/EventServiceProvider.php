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
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Listeners\FilterWpQuery;
    use WPEmerge\Session\WpLoginAction;
    use WPEmerge\View\ViewFactory;

    class EventServiceProvider extends ServiceProvider
    {

        private $mapped_events = [

            'template_include' => ['resolve', IncomingWebRequest::class, 3001],
            'admin_init' => ['resolve', LoadedWpAdmin::class, 3001],
            'request' => ['resolve', WpQueryFilterable::class, 3001],
            'init' => ['resolve', LoadedWP::class, -999],
            'in_admin_footer' => [StartLoadingAdminFooter::class, 1],
            'wp_login' => ['resolve', WpLoginAction::class],
            'wp_logout' => ['resolve', WpLoginAction::class]

        ];

        private $event_listeners = [

            IncomingWebRequest::class => [

                [HttpKernel::class, 'run']

            ],

            IncomingAdminRequest::class => [

                [OutputBufferMiddleware::class, 'start'],

            ],

            IncomingAjaxRequest::class => [

                [HttpKernel::class, 'run']

            ],

            WpLoginAction::class => [

                [HttpKernel::class, 'run']

            ],

            StartLoadingAdminFooter::class => [

                [OutputBufferMiddleware::class, 'flush'],

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

                [ViewFactory::class, 'compose'],

            ],

            WpQueryFilterable::class => [
                [FilterWpQuery::class, 'handle']
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