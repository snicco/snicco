<?php


    declare(strict_types = 1);


    namespace WPEmerge\Application;

    use Contracts\ContainerAdapter;
    use Nyholm\Psr7\Factory\Psr17Factory;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Psr\Http\Message\UploadedFileFactoryInterface;
    use Psr\Http\Message\UriFactoryInterface;
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
            RoutingServiceProvider::class,
            HttpServiceProvider::class,
            MiddlewareServiceProvider::class,
            ViewServiceProvider::class,
            MailServiceProvider::class,

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

            // c  $server_request = $server_request ?? $this->captureRequest();

            $this->setContainer($container);
            $this->container()->instance(Application::class, $this);
            $this->container()->instance(ContainerAdapter::class, $this->container());



            WpFacade::setFacadeContainer($container);

            $this->bindApplicationTrait();

        }

        public function setServerRequestFactory(ServerRequestFactoryInterface $server_request_factory) : Application
        {

            $this->container()
                 ->instance(ServerRequestFactoryInterface::class, $server_request_factory);

            return $this;
        }

        public function setUriFactory(UriFactoryInterface $uri_factory) : Application
        {

            $this->container()->instance(UriFactoryInterface::class, $uri_factory);

            return $this;
        }

        public function setUploadedFileFactory(UploadedFileFactoryInterface $file_factory) : Application
        {

            $this->container()->instance(UploadedFileFactoryInterface::class, $file_factory);

            return $this;
        }

        public function setStreamFactory(StreamFactoryInterface $stream_factory) : Application
        {

            $this->container()->instance(StreamFactoryInterface::class, $stream_factory);

            return $this;
        }

        public function setResponseFactory(ResponseFactoryInterface $response_factory) : Application
        {
            $this->container()->instance(ResponseFactoryInterface::class, $response_factory);
            return $this;
        }

        public static function create(string $base_path, ContainerAdapter $container_adapter) : Application
        {

            $app = new static($container_adapter);
            $app->setBasePath($base_path);

            return $app;

        }

        public function boot(bool $load = true) : void
        {

            if ($this->bootstrapped) {

                throw new ConfigurationException(static::class.' already bootstrapped.');

            }

            $this->config = ((new LoadConfiguration))->bootstrap($this);
            $this->container()->instance(ApplicationConfig::class, $this->config);
            $this->container()->instance(ServerRequestCreator::class, $this->serverRequestCreator());

            if ( ! $load ) {
                return;
            }

            $this->captureRequest();

            $this->loadServiceProviders();

            $this->bootstrapped = true;



        }

        public function config(?string $key = null, $default = null)
        {

            if ( ! $key) {

                return $this->config;

            }

            return $this->config->get($key, $default);

        }

        public function runningUnitTest()
        {

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

        private function captureRequest()
        {

            $psr_request = $this->serverRequestCreator()->fromGlobals();

            $request = new Request($psr_request);

            $this->container()->instance(Request::class, $request);


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

        public function serverRequestCreator() : ServerRequestCreator
        {

            return new ServerRequestCreator(
                $this->container()->make(ServerRequestFactoryInterface::class),
                $this->container()->make(UriFactoryInterface::class),
                $this->container()->make(UploadedFileFactoryInterface::class),
                $this->container()->make(StreamFactoryInterface::class)
            );

        }


    }
