<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\Event\TerminatedResponse;
use Snicco\Bundle\HttpRouting\Middleware\ErrorsToExceptions;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\ResponseEmitter\LaminasEmitterStack;
use Snicco\Bundle\HttpRouting\ResponseEmitter\ResponseEmitter;
use Snicco\Bundle\HttpRouting\ResponseEmitter\TestEmitter;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\LazyHttpErrorHandler;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Cache\FileRouteCache;
use Snicco\Component\HttpRouting\Routing\Cache\NullCache;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Delegating;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\DisplayerFilter;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use Snicco\Component\Psr7ErrorHandler\ProductionErrorHandler;
use Throwable;

use function array_map;
use function array_replace;
use function class_exists;
use function class_implements;
use function copy;
use function count;
use function dirname;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function sprintf;

final class HttpRoutingBundle implements Bundle
{
    public const ALIAS = 'sniccowp/http-routing-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->configureRouting($config, $kernel);
        $this->configureMiddleware($config, $kernel);
        $this->configureErrorHandling($config, $kernel);
    }

    public function register(Kernel $kernel): void
    {
        if (! class_exists(BetterWPHooksBundle::class) || ! $kernel->usesBundle(BetterWPHooksBundle::ALIAS)) {
            throw new RuntimeException('The http-routing-bundle needs the sniccowp-better-wp-hooks-bundle to run.');
        }

        $container = $kernel->container();

        $this->bindHttpRunner($kernel);
        $this->bindAdminMenu($container);
        $this->bindLogger($kernel);
        $this->bindPsr17Discovery($container);
        $this->bindResponseFactory($container);
        $this->bindServerRequestCreator($container);
        $this->bindRouter($kernel);
        $this->bindUrlGenerator($container);
        $this->bindUrlMatcher($container);
        $this->bindRoutes($container);
        $this->bindErrorHandler($container, $kernel);
        $this->bindMiddlewarePipeline($container);
        $this->bindRoutingMiddleware($container);
        $this->bindRouteRunnerMiddleware($container, $kernel);
        $this->bindErrorHandlingMiddleware($container);
        $this->bindResponsePostProcessor($kernel);
    }

    public function bootstrap(Kernel $kernel): void
    {
        $dispatcher = $kernel->container()->make(EventDispatcher::class);
        $dispatcher->listen(TerminatedResponse::class, ResponsePostProcessor::class);
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function bindRouter(Kernel $kernel): void
    {
        $kernel->container()->shared(Router::class, function () use ($kernel) {
            $container = $kernel->container();
            $config = $kernel->config();
            $env = $kernel->env();

            $context = new UrlGenerationContext(
                $config->getString(RoutingOption::key(RoutingOption::HOST)),
                $config->getInteger(RoutingOption::key(RoutingOption::HTTPS_PORT)),
                $config->getInteger(RoutingOption::key(RoutingOption::HTTP_PORT)),
                $config->getBoolean(RoutingOption::key(RoutingOption::USE_HTTPS))
            );

            $loader = $container[RouteLoader::class] ?? new PHPFileRouteLoader(
                $config->getListOfStrings(RoutingOption::key(RoutingOption::ROUTE_DIRECTORIES)),
                $config->getListOfStrings(RoutingOption::key(RoutingOption::API_ROUTE_DIRECTORIES)),
                $container[RouteLoadingOptions::class] ?? new DefaultRouteLoadingOptions(
                    $config->getString(RoutingOption::key(RoutingOption::API_PREFIX))
                ),
            );

            $cache = ($env->isStaging() || $env->isProduction())
                ? new FileRouteCache(
                    $kernel->directories()->cacheDir() . '/prod.routes-generated.php'
                )
                : new NullCache();

            $admin_area = new WPAdminArea(
                $config->getString(RoutingOption::key(RoutingOption::WP_ADMIN_PREFIX)),
                $config->getString(RoutingOption::key(RoutingOption::WP_LOGIN_PATH))
            );

            return new Router(
                $context,
                $loader,
                $cache,
                $admin_area,
                null,
                null,
            );
        });
    }

    private function bindUrlGenerator(DIContainer $container): void
    {
        $container->shared(UrlGenerator::class, fn () => $container->make(Router::class)->urlGenerator());
    }

    private function bindUrlMatcher(DIContainer $container): void
    {
        $container->shared(UrlMatcher::class, fn () => $container->make(Router::class)->urlMatcher());
    }

    private function bindAdminMenu(DIContainer $container): void
    {
        $container->shared(AdminMenu::class, function () use ($container) {
            return $container->make(Router::class)->adminMenu();
        });
        $container->shared(WPAdminMenu::class, function () use ($container) {
            return new WPAdminMenu($container->make(AdminMenu::class));
        });
    }

    private function bindErrorHandler(DIContainer $container, Kernel $kernel): void
    {
        $container->shared(HttpErrorHandler::class, function () use ($container, $kernel) {
            $error_logger = $container[TestLogger::class] ?? $container->make(LoggerInterface::class);

            $information_provider = $this->informationProvider($kernel);

            $displayer_filter = $container[DisplayerFilter::class] ?? new Delegating(
                new ContentType(),
                new Verbosity($kernel->env()->isDebug()),
                new CanDisplay(),
            );

            $log_context = array_map(
                function ($class) {
                    /** @var class-string<RequestLogContext> $class */
                    return new $class();
                },
                $kernel->config()->getListOfStrings(
                    HttpErrorHandlingOption::key(HttpErrorHandlingOption::REQUEST_LOG_CONTEXT)
                )
            );

            /** @var array<class-string<Throwable>,string> $log_levels */
            $log_levels = $kernel->config()->getArray(
                HttpErrorHandlingOption::key(HttpErrorHandlingOption::LOG_LEVELS)
            );

            $logger = new RequestAwareLogger(
                $error_logger,
                $log_levels,
                ...$log_context
            );

            $displayers = array_map(function ($class) use ($container) {
                /** @var class-string<ExceptionDisplayer> $class */
                return $container[$class] ?? new $class();
            }, $kernel->config()->getListOfStrings(HttpErrorHandlingOption::key(HttpErrorHandlingOption::DISPLAYERS)));

            return new ProductionErrorHandler(
                $container->make(ResponseFactoryInterface::class),
                $logger,
                $information_provider,
                $displayer_filter,
                ...$displayers
            );
        });
    }

    private function bindMiddlewarePipeline(DIContainer $container): void
    {
        $container->factory(MiddlewarePipeline::class, function () use ($container) {
            return new MiddlewarePipeline(
                $container,
                new LazyHttpErrorHandler($container)
            );
        });
    }

    private function bindRoutingMiddleware(DIContainer $container): void
    {
        $container->shared(RoutingMiddleware::class, function () use ($container) {
            return new RoutingMiddleware(
                $container->make(UrlMatcher::class)
            );
        });
    }

    private function bindRouteRunnerMiddleware(DIContainer $container, Kernel $kernel): void
    {
        $container->shared(RouteRunner::class, function () use ($container, $kernel) {
            $middleware_resolver = ($kernel->env()->isProduction() || $kernel->env()->isStaging())
                ? $this->getCachedMiddlewareResolver($kernel)
                : $this->getMiddlewareResolver($kernel);

            return new RouteRunner(
                $container->make(MiddlewarePipeline::class),
                $middleware_resolver,
                $container
            );
        });
    }

    private function bindResponseFactory(DIContainer $container): void
    {
        $container->shared(ResponseFactory::class, function () use ($container) {
            $discovery = $container->make(Psr17FactoryDiscovery::class);
            return new ResponseFactory(
                $discovery->createResponseFactory(),
                $discovery->createStreamFactory(),
            );
        });
        $container->shared(ResponseFactoryInterface::class, fn () => $container->make(ResponseFactory::class));
        $container->shared(StreamFactoryInterface::class, fn () => $container->make(ResponseFactory::class));
    }

    private function bindPsr17Discovery(DIContainer $container): void
    {
        $container->shared(Psr17FactoryDiscovery::class, function (): Psr17FactoryDiscovery {
            return new Psr17FactoryDiscovery();
        });
    }

    private function bindServerRequestCreator(DIContainer $container): void
    {
        $container->shared(ServerRequestCreator::class, function () use ($container): ServerRequestCreator {
            $discovery = $container->make(Psr17FactoryDiscovery::class);
            return new ServerRequestCreator(
                $discovery->createServerRequestFactory(),
                $discovery->createUriFactory(),
                $discovery->createUploadedFileFactory(),
                $discovery->createStreamFactory()
            );
        });
    }

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function getMiddlewareResolver(Kernel $kernel): MiddlewareResolver
    {
        $config = $kernel->config();

        return new MiddlewareResolver(
            $config->getArray(MiddlewareOption::key(MiddlewareOption::ALWAYS_RUN)),
            $config->getArray(MiddlewareOption::key(MiddlewareOption::ALIASES)),
            $config->getArray(MiddlewareOption::key(MiddlewareOption::GROUPS)),
            $config->getArray(MiddlewareOption::key(MiddlewareOption::PRIORITY_LIST))
        );
    }

    private function getCachedMiddlewareResolver(Kernel $kernel): MiddlewareResolver
    {
        $cache_file = $kernel->directories()->cacheDir() . '/prod.middleware-map-generated.php';

        return MiddlewareCache::get($cache_file, function () use ($kernel) {
            $resolver = $this->getMiddlewareResolver($kernel);
            $routes = $kernel->container()->make(Routes::class);
            return $resolver->createMiddlewareCache($routes, $kernel->container());
        });
    }

    private function bindRoutes(DIContainer $container): void
    {
        $container->shared(Routes::class, fn () => $container->make(Router::class)->routes());
    }

    private function bindLogger(Kernel $kernel): void
    {
        if ($kernel->env()->isTesting()) {
            $kernel->container()->shared(TestLogger::class, fn () => new TestLogger());
            $kernel->container()->shared(
                LoggerInterface::class,
                fn () => $kernel->container()->make(TestLogger::class)
            );
            return;
        }

        if (! $kernel->container()->has(LoggerInterface::class)) {
            $kernel->container()->shared(LoggerInterface::class, fn () => new StdErrLogger(
                $kernel->config()->getString(HttpErrorHandlingOption::key(HttpErrorHandlingOption::LOG_PREFIX))
            ));
        }
    }

    private function informationProvider(Kernel $kernel): ExceptionInformationProvider
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $provider = $container[ExceptionInformationProvider::class] ?? null;

        if ($provider) {
            return $provider;
        }

        $identifier = new SplHashIdentifier();
        $transformers = array_map(function ($class) {
            /** @var class-string<ExceptionTransformer> $class */
            return new $class();
        }, $config->getListOfStrings(HttpErrorHandlingOption::key(HttpErrorHandlingOption::TRANSFORMERS)));

        return InformationProviderWithTransformation::fromDefaultData($identifier, ...$transformers);
    }

    private function configureRouting(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/routing.php';
        $merged = array_replace($defaults, $config->getArray('routing', []));

        $config->set('routing', $merged);

        if (empty($config->getString('routing.' . RoutingOption::HOST))) {
            throw new InvalidArgumentException(
                'routing.' . RoutingOption::HOST . ' must be a non-empty-string.'
            );
        }

        // quick type checks.
        $config->getInteger(RoutingOption::key(RoutingOption::HTTP_PORT));
        $config->getInteger(RoutingOption::key(RoutingOption::HTTPS_PORT));
        $config->getBoolean(RoutingOption::key(RoutingOption::USE_HTTPS));

        if (empty($config->getString(RoutingOption::key(RoutingOption::WP_ADMIN_PREFIX)))) {
            throw new InvalidArgumentException(
                RoutingOption::key(RoutingOption::WP_ADMIN_PREFIX) . ' must be a non-empty string.'
            );
        }

        if (empty($config->getString(RoutingOption::key(RoutingOption::WP_LOGIN_PATH)))) {
            throw new InvalidArgumentException(
                RoutingOption::key(RoutingOption::WP_LOGIN_PATH) . ' must be a non-empty string.'
            );
        }

        foreach ($config->getListOfStrings(RoutingOption::key(RoutingOption::ROUTE_DIRECTORIES)) as $dir) {
            if (! is_readable($dir)) {
                throw new InvalidArgumentException(
                    sprintf(
                        RoutingOption::key(RoutingOption::ROUTE_DIRECTORIES)
                        . " must be a list of readable directories.\nPath [%s] is not readable.",
                        $dir
                    )
                );
            }
        }

        $api_dirs = $config->getListOfStrings(RoutingOption::key(RoutingOption::API_ROUTE_DIRECTORIES));

        if (count($api_dirs)) {
            $prefix = $config->getString(RoutingOption::key(RoutingOption::API_PREFIX));
            if ('' === $prefix) {
                throw new InvalidArgumentException(
                    RoutingOption::key(
                        RoutingOption::API_PREFIX
                    ) . ' must be a non-empty-string if API routes are used.'
                );
            }
        }

        foreach ($api_dirs as $dir) {
            if (! is_readable($dir)) {
                throw new InvalidArgumentException(
                    sprintf(
                        RoutingOption::key(RoutingOption::API_ROUTE_DIRECTORIES)
                        . " must be a list of readable directories.\nPath [%s] is not readable.",
                        $dir
                    )
                );
            }
        }

        $this->copyConfig($kernel, 'routing');
    }

    private function configureMiddleware(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/middleware.php';
        $config->set(
            'middleware',
            array_replace($defaults, $config->getArray('middleware', []))
        );

        foreach ($config->getArray(MiddlewareOption::key(MiddlewareOption::GROUPS)) as $key => $middleware) {
            if (! is_string($key)) {
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::GROUPS
                    ) . " has to an associative array of string => array pairs.\nGot key [$key]."
                );
            }
            if (! is_array($middleware)) {
                $type = gettype($middleware);
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::GROUPS
                    ) . " has to an associative array of string => array pairs.\nGot [$type] for key [$key]."
                );
            }

            /**
             * @psalm-suppress MixedAssignment
             */
            foreach ($middleware as $index => $m) {
                if (! is_string($m)) {
                    $type = gettype($m);
                    throw new InvalidArgumentException(
                        "Middleware group [$key] has to contain only strings.\nGot [$type] at index [$index]."
                    );
                }
            }
        }

        foreach ($config->getArray(MiddlewareOption::key(MiddlewareOption::ALIASES)) as $alias => $class) {
            if (! is_string($alias)) {
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::ALIASES
                    ) . ' has to be an array of string => middleware-class pairs.'
                );
            }
            if (! is_string($class)
                || ! class_exists($class)
                || ! in_array(
                    MiddlewareInterface::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    "Middleware alias [$alias] has to resolve to a middleware class."
                );
            }
        }

        foreach ($config->getListOfStrings(MiddlewareOption::key(MiddlewareOption::PRIORITY_LIST)) as $class) {
            if (
                ! class_exists($class)
                || ! in_array(
                    MiddlewareInterface::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::PRIORITY_LIST
                    ) . " has to be a list of middleware class-strings.\nGot [$class]."
                );
            }
        }

        foreach ($config->getListOfStrings(MiddlewareOption::key(MiddlewareOption::KERNEL_MIDDLEWARE)) as $class) {
            if (
                ! class_exists($class)
                || ! in_array(
                    MiddlewareInterface::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::KERNEL_MIDDLEWARE
                    ) . " has to be a list of middleware class-strings.\nGot [$class]."
                );
            }
        }

        $valid = [
            RoutingConfigurator::FRONTEND_MIDDLEWARE,
            RoutingConfigurator::ADMIN_MIDDLEWARE,
            RoutingConfigurator::API_MIDDLEWARE,
            RoutingConfigurator::GLOBAL_MIDDLEWARE,
        ];

        foreach ($config->getListOfStrings(MiddlewareOption::key(MiddlewareOption::ALWAYS_RUN)) as $group_name) {
            if (! in_array($group_name, $valid, true)) {
                throw new InvalidArgumentException(
                    MiddlewareOption::key(
                        MiddlewareOption::ALWAYS_RUN
                    ) . " can only contain [frontend,api,admin,global].\nGot [$group_name]."
                );
            }
        }

        $this->copyConfig($kernel, 'middleware');
    }

    private function configureErrorHandling(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/http_error_handling.php';

        $config->set(
            'http_error_handling',
            array_replace($defaults, $config->getArray('http_error_handling', []))
        );

        foreach (
            $config->getListOfStrings(
                HttpErrorHandlingOption::key(HttpErrorHandlingOption::DISPLAYERS)
            ) as $class
        ) {
            if (
                ! class_exists($class)
                || ! in_array(
                    ExceptionDisplayer::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    HttpErrorHandlingOption::key(
                        HttpErrorHandlingOption::DISPLAYERS
                    ) . ' has to be a list of class-strings implementing ' . ExceptionDisplayer::class . ".\nGot [$class]."
                );
            }
        }

        foreach (
            $config->getListOfStrings(
                HttpErrorHandlingOption::key(HttpErrorHandlingOption::TRANSFORMERS)
            ) as $class
        ) {
            if (
                ! class_exists($class)
                || ! in_array(
                    ExceptionTransformer::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    HttpErrorHandlingOption::key(
                        HttpErrorHandlingOption::TRANSFORMERS
                    ) . ' has to be a list of class-strings implementing ' . ExceptionTransformer::class . ".\nGot [$class]."
                );
            }
        }

        foreach (
            $config->getListOfStrings(
                HttpErrorHandlingOption::key(HttpErrorHandlingOption::REQUEST_LOG_CONTEXT)
            ) as $class
        ) {
            if (
                ! class_exists($class)
                || ! in_array(
                    RequestLogContext::class,
                    (array) class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    HttpErrorHandlingOption::key(
                        HttpErrorHandlingOption::REQUEST_LOG_CONTEXT
                    ) . ' has to be a list of class-strings implementing ' . RequestLogContext::class . ".\nGot [$class]."
                );
            }
        }

        $valid_levels = [
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::INFO,
            LogLevel::WARNING,
            LogLevel::ALERT,
            LogLevel::NOTICE,
            LogLevel::EMERGENCY,
            LogLevel::DEBUG,
        ];

        foreach (
            $config->getArray(
                HttpErrorHandlingOption::key(HttpErrorHandlingOption::LOG_LEVELS)
            ) as $class => $level
        ) {
            if (! is_string($class)
                || ! class_exists($class)
                || ! in_array(
                    Throwable::class,
                    (array) class_implements($class)
                )) {
                $class = (string) $class;
                throw new InvalidArgumentException(
                    "[$class] is not a valid exception class-string for " . HttpErrorHandlingOption::key(
                        HttpErrorHandlingOption::LOG_LEVELS
                    )
                );
            }

            if (! is_string($level) || ! in_array($level, $valid_levels, true)) {
                $level = (string) $level;

                throw new InvalidArgumentException(
                    sprintf(
                        "[$level] is not a valid PSR-3 log-level for exception class " . $class . "\nValid levels: [%s]",
                        implode(',', $valid_levels)
                    )
                );
            }
        }

        $this->copyConfig($kernel, 'http_error_handling');
    }

    private function bindHttpRunner(Kernel $kernel): void
    {
        // The HttpKernel needs to be resolvable on it's on so that we can use it in functional tests.
        $kernel->container()->shared(HttpKernel::class, function () use ($kernel) {
            /** @var class-string<MiddlewareInterface>[] $kernel_middleware */
            $kernel_middleware = $kernel->config()->getListOfStrings(
                MiddlewareOption::key(MiddlewareOption::KERNEL_MIDDLEWARE)
            );

            $container = $kernel->container();

            return new HttpKernel(
                $container->make(MiddlewarePipeline::class),
                new ResponsePreparation($container->make(StreamFactoryInterface::class)),
                $container->make(EventDispatcherInterface::class),
                $kernel_middleware
            );
        });

        $kernel->container()->shared(HttpKernelRunner::class, function () use ($kernel) {
            $container = $kernel->container();

            $dispatcher = $container->make(EventDispatcher::class);

            $emitter = $this->getResponseEmitter($kernel, $dispatcher);

            $api_prefix = $kernel->config()->getString(RoutingOption::key(RoutingOption::API_PREFIX));

            return new HttpKernelRunner(
                $container->make(HttpKernel::class),
                $container->make(ServerRequestCreator::class),
                $dispatcher,
                $emitter,
                $container->make(StreamFactoryInterface::class),
                '' === $api_prefix ? null : $api_prefix
            );
        });
    }

    private function bindResponsePostProcessor(Kernel $kernel): void
    {
        $kernel->container()->shared(ResponsePostProcessor::class, function () use ($kernel) {
            return new ResponsePostProcessor($kernel->env());
        });
    }

    private function getResponseEmitter(Kernel $kernel, EventDispatcher $dispatcher): ResponseEmitter
    {
        if ($kernel->env()->isTesting()) {
            return new TestEmitter($dispatcher);
        }

        return $kernel->container()[ResponseEmitter::class] ?? new LaminasEmitterStack();
    }

    private function bindErrorHandlingMiddleware(DIContainer $container): void
    {
        $container->shared(ErrorsToExceptions::class, function () use ($container) {
            return new ErrorsToExceptions(
                $container->make(LoggerInterface::class)
            );
        });
    }

    private function copyConfig(Kernel $kernel, string $namespace): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()->configDir() . '/' . $namespace . '.php';

        if (is_file($destination)) {
            return;
        }

        $copied = copy(
            dirname(__DIR__) . "/config/$namespace.php",
            $destination
        );

        if (false === $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not copy default routing.php config to path $destination");
            // @codeCoverageIgnoreEnd
        }
    }
}
