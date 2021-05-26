<?php


    declare(strict_types = 1);


    namespace WPEmerge\Application;

    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewFactoryInterface;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Facade\WordpressApi;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\PhpViewEngine;
    use WPEmerge\View\ViewComposerCollection;

    class ApplicationServiceProvider extends ServiceProvider
    {


        public function register() : void
        {
            $this->bindConfig();
            $this->bindShutDownHandler();
            $this->bindAliases();
        }

        public function bootstrap() : void
        {

            //

        }

        private function bindShutDownHandler()
        {

            $this->container->singleton(ShutdownHandler::class, function () {

                return new ShutdownHandler($this->requestType());

            });
        }

        private function bindConfig()
        {

            $this->container->instance('request.type', $this->requestType());

        }

        private function bindAliases()
        {

            $app = $this->container->make( Application::class );

            $this->applicationAliases( $app );
            $this->responseAliases( $app );
            $this->routingAliases( $app );
            $this->viewAliases( $app );
            $this->sessionAliases( $app );

        }

        private function applicationAliases( Application $app ) {

            $app->alias( 'app', Application::class );

        }

        private function responseAliases( Application $app ) {

            //

        }

        private function routingAliases( Application $app ) {

            $app->alias( 'route', Router::class );
            $app->alias( 'routeUrl', UrlGenerator::class, 'toRoute' );
            $app->alias( 'post', Router::class, 'post' );
            $app->alias( 'get', Router::class, 'get' );
            $app->alias( 'patch', Router::class, 'patch' );
            $app->alias( 'put', Router::class, 'put' );
            $app->alias( 'options', Router::class, 'options' );
            $app->alias( 'delete', Router::class, 'delete' );
            $app->alias( 'match', Router::class, 'match' );

        }

        private function viewAliases( Application $app ) {

            $app->alias( 'globals', function () use ( $app ) {

                return $app->resolve( 'composers.globals' );

            } );
            $app->alias( 'addComposer', function () use ( $app ) {

                $composer_collection = $app->resolve( ViewComposerCollection::class );

                $args = func_get_args();

                $composer_collection->addComposer( ...$args );

            } );
            $app->alias( 'view', function () use ( $app ) {

                /** @var ViewFactoryInterface $view_service */
                $view_service = $app->container()->make(ViewFactoryInterface::class);

                return call_user_func_array( [ $view_service, 'make' ], func_get_args() );

            } );
            $app->alias( 'render', function () use ( $app ) {

                /** @var ViewFactoryInterface $view_service */
                $view_service = $app->container()->make(ViewFactoryInterface::class);

                $view_as_string = call_user_func_array( [ $view_service, 'render', ], func_get_args() );

                echo $view_as_string;

            } );
            $app->alias( 'includeChildViews', function () use ( $app ) {

                /** @var PhpViewEngine $engine */
                $engine = $app->resolve( PhpViewEngine::class );

                $engine->includeNextView();


            } );

        }

        private function sessionAliases( Application $app ) {

            //

        }


    }
