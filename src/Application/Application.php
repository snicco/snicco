<?php


    declare(strict_types = 1);


    namespace WPEmerge\Application;

    use Contracts\ContainerAdapter;
    use Nyholm\Psr7\Factory\Psr17Factory;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ServerRequestInterface;
    use SniccoAdapter\BaseContainerAdapter;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Events\EventServiceProvider;
    use WPEmerge\ExceptionHandling\ExceptionServiceProvider;
    use WPEmerge\Factories\FactoryServiceProvider;
    use WPEmerge\Http\HttpServiceProvider;
    use WPEmerge\Mail\MailServiceProvider;
    use WPEmerge\Middleware\MiddlewareServiceProvider;
    use WPEmerge\Routing\RoutingServiceProvider;
    use WPEmerge\View\ViewServiceProvider;
    use WPEmerge\Facade\WpFacade;

    class Application
    {

        use ManagesAliases;
        use LoadsServiceProviders;
        use HasContainer;

        const CORE_SERVICE_PROVIDERS = [

            EventServiceProvider::class,
            ExceptionServiceProvider::class,
            FactoryServiceProvider::class,
            ApplicationServiceProvider::class,
            HttpServiceProvider::class,
            RoutingServiceProvider::class,
            MiddlewareServiceProvider::class,
            ViewServiceProvider::class,
            MailServiceProvider::class

        ];

        private $bootstrapped = false;

        /**
         * @var ApplicationConfig
         */
        private $config;

        /**
         * @var bool
         */
        private $running_unit_test = false;

        /**
         * @var string
         */
        private $base_path;

        public function __construct(ContainerAdapter $container, ServerRequestInterface $server_request = null)
        {

            $server_request = $server_request ?? $this->captureRequest();

            $this->setContainer($container);
            $this->container()->instance(Application::class, $this);
            $this->container()->instance(ContainerAdapter::class, $this->container());

            $request = new Request($server_request);

            $this->bindRequest($request);
            $this->bindServerRequest($request);

            WpFacade::setFacadeContainer($container);

            $this->bindApplicationTrait();

        }

        public static function create(string $base_path, ContainerAdapter $container_adapter) : Application
        {
            $app = new static($container_adapter);
            $app->setBasePath($base_path);
            return $app;

        }

        public function boot( bool $load_providers = true ) : void
        {

            if ($this->bootstrapped) {

                throw new ConfigurationException(static::class.' already bootstrapped.');

            }

            $this->config = ((new LoadConfiguration))->bootstrap($this);
            $this->container()->instance(ApplicationConfig::class, $this->config);

            if ( ! $load_providers ) {
                return;
            }

            $this->loadServiceProviders();

            $this->bootstrapped = true;


            // If we would always unregister here it would not be possible to handle
            // any errors that happen between this point and the the triggering of the
            // hooks that run the HttpKernel.
            if ( ! $this->handlesExceptionsGlobally() ) {

                /** @var ErrorHandlerInterface $error_handler */
                $error_handler = $this->container()->make(ErrorHandlerInterface::class);
                $error_handler->unregister();

            }


        }

        public function handlesExceptionsGlobally()
        {

            return $this->config->get('exception_handling.global', false);

        }

        public function config(?string $key = null, $default = null)
        {

            if ( ! $key ) {

                return $this->config;

            }

            return $this->config->get($key, $default);

        }

        public function runningUnitTest() {

            $this->running_unit_test = true;

        }

        public static function generateKey() : string
        {

            return 'base64:'.base64_encode(random_bytes(32));

        }

        public function isRunningUnitTest() : bool
        {
            return $this->running_unit_test;
        }

        private function captureRequest() : ServerRequestInterface
        {

            $factory = $factory ?? new Psr17Factory();
            $creator = new ServerRequestCreator(
                $factory,
                $factory,
                $factory,
                $factory
            );

            return $creator->fromGlobals();

        }

        /**
         *
         * This is the request object that all classes in the app rely on.
         * This object is gets rebound during the request cycle.
         * I.E before/after running the middleware stack or running the route handler.
         *
         * @param  Request  $changing_request
         */
        private function bindRequest(Request $changing_request)
        {
            $this->container()->instance(Request::class, $changing_request);
        }

        /**
         *
         * This request is the one that got created from the PHP Globals.
         * It should only be used during bootstrapping of the Application.
         *
         * @param  Request  $base_request
         */
        private function bindServerRequest(Request $base_request)
        {
            $this->container()->instance(ServerRequestInterface::class, $base_request);
        }

        private function bindApplicationTrait()
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

            $last = end($trace)['class'];

            $this->container()->instance(ApplicationTrait::class, $last);
        }

        private function setBasePath(string $base_path)
        {
            $this->base_path = rtrim($base_path, '\/');
        }

        public function basePath() : string
        {
            return $this->base_path;
        }

        public function configPath($path = '') : string
        {
            return $this->base_path.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
        }


    }
