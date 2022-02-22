<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting;

use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
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
use Snicco\Component\HttpRouting\Routing\Routing;
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
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use Throwable;

use function array_map;
use function class_exists;
use function class_implements;
use function gettype;
use function implode;
use function in_array;
use function is_array;
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
        if (!$config->has('routing.' . RoutingOption::HOST)) {
            throw new InvalidArgumentException('routing.' . RoutingOption::HOST . ' must be a non-empty-string.');
        }

        $config->extend('routing.' . RoutingOption::WP_ADMIN_PREFIX, '/wp-admin');
        $config->extend('routing.' . RoutingOption::WP_LOGIN_PATH, '/wp-login.php');
        $config->extend('routing.' . RoutingOption::ROUTE_DIRECTORIES, []);
        $config->extend('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, []);
        $config->extend('routing.' . RoutingOption::API_PREFIX, '/');

        if (!$config->has('routing.' . RoutingOption::MIDDLEWARE_GROUPS)) {
            $config->set('routing.' . RoutingOption::MIDDLEWARE_GROUPS, []);
        }

        $config->extend('routing.' . RoutingOption::MIDDLEWARE_ALIASES, []);
        $config->extend('routing.' . RoutingOption::MIDDLEWARE_PRIORITY, []);
        $config->extend('routing.' . RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS, []);
        $config->extend('routing.' . RoutingOption::HTTP_PORT, 80);
        $config->extend('routing.' . RoutingOption::HTTPS_PORT, 443);
        $config->extend('routing.' . RoutingOption::HTTPS, true);
        $config->extend('routing.' . RoutingOption::EXCEPTION_DISPLAYERS, []);
        $config->extend('routing.' . RoutingOption::EXCEPTION_TRANSFORMERS, []);
        $config->extend('routing.' . RoutingOption::EXCEPTION_REQUEST_CONTEXT, []);
        $config->extend('routing.' . RoutingOption::EXCEPTION_LOG_LEVELS, []);

        $this->validateConfig($config);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $this->bindPsr17Discovery($container);
        $this->bindResponseFactory($container);
        $this->bindServerRequestCreator($container);
        $this->bindRoutingFacade($kernel);
        $this->bindUrlGenerator($container);
        $this->bindUrlMatcher($container);
        $this->bindAdminMenu($container);
        $this->bindRoutes($container);
        $this->bindLogger($kernel);
        $this->bindErrorHandler($container, $kernel);
        $this->bindMiddlewarePipeline($container);
        $this->bindRoutingMiddleware($container);
        $this->bindRouteRunnerMiddleware($container, $kernel);
    }

    public function bootstrap(Kernel $kernel): void
    {
        // Nothing to bootstrap. Everything is lazy loaded.
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function bindRoutingFacade(Kernel $kernel): void
    {
        $kernel->container()->singleton(Routing::class, function () use ($kernel) {
            $container = $kernel->container();
            $config = $kernel->config();
            $env = $kernel->env();

            $context = new UrlGenerationContext(
                $config->getString('routing.' . RoutingOption::HOST),
                $config->getInteger('routing.' . RoutingOption::HTTPS_PORT),
                $config->getInteger('routing.' . RoutingOption::HTTP_PORT),
                $config->getBoolean('routing.' . RoutingOption::HTTPS)
            );

            $loader = $container[RouteLoader::class] ?? new PHPFileRouteLoader(
                    $config->getListOfStrings('routing.' . RoutingOption::ROUTE_DIRECTORIES),
                    $config->getListOfStrings('routing.' . RoutingOption::API_ROUTE_DIRECTORIES),
                    $container[RouteLoadingOptions::class] ?? new DefaultRouteLoadingOptions(
                        $config->getString('routing.' . RoutingOption::API_PREFIX)
                    ),
                );

            $cache = ($env->isStaging() || $env->isProduction())
                ? new FileRouteCache(
                    $kernel->directories()->cacheDir() . '/prod.routes-generated.php'
                )
                : new NullCache();

            $admin_area = new WPAdminArea(
                $config->getString('routing.' . RoutingOption::WP_ADMIN_PREFIX),
                $config->getString('routing.' . RoutingOption::WP_LOGIN_PATH)
            );

            return new Routing(
                $container,
                $context,
                $loader,
                $cache,
                $admin_area
            );
        });
    }

    private function bindUrlGenerator(DIContainer $container): void
    {
        $container->singleton(UrlGenerator::class, fn() => $container->make(Routing::class)->urlGenerator());
    }

    private function bindUrlMatcher(DIContainer $container): void
    {
        $container->singleton(UrlMatcher::class, fn() => $container->make(Routing::class)->urlMatcher());
    }

    private function bindAdminMenu(DIContainer $container): void
    {
        $container->singleton(AdminMenu::class, fn() => $container->make(Routing::class)->adminMenu());
    }

    private function bindErrorHandler(DIContainer $container, Kernel $kernel): void
    {
        $container->singleton(HttpErrorHandlerInterface::class, function () use ($container, $kernel) {
            $error_logger = $container[TestLogger::class] ?? $container->make(LoggerInterface::class);

            $information_provider = $this->informationProvider($kernel);

            $displayer_filter = $container[DisplayerFilter::class] ?? new Delegating(
                    new ContentType(),
                    new Verbosity($kernel->env()->isDebug()),
                    new CanDisplay(),
                );

            $log_context = array_map(function ($class) {
                /** @var class-string<RequestLogContext> $class */
                return new $class;
            }, $kernel->config()->getListOfStrings('routing.' . RoutingOption::EXCEPTION_REQUEST_CONTEXT));

            /** @var array<class-string<Throwable>,string> $log_levels */
            $log_levels = $kernel->config()->getArray('routing.' . RoutingOption::EXCEPTION_LOG_LEVELS);

            $logger = new RequestAwareLogger(
                $error_logger,
                $log_levels,
                ...$log_context
            );

            $displayers = array_map(function ($class) {
                /** @var class-string<ExceptionDisplayer> $class */
                return new $class;
            }, $kernel->config()->getListOfStrings('routing.' . RoutingOption::EXCEPTION_DISPLAYERS));

            return new HttpErrorHandler(
                $container->make(ResponseFactoryInterface::class),
                $logger,
                $information_provider,
                $displayer_filter,
                ...$displayers
            );
        }
        );
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
        $container->singleton(RoutingMiddleware::class, function () use ($container) {
            return new RoutingMiddleware(
                $container->make(UrlMatcher::class)
            );
        });
    }

    private function bindRouteRunnerMiddleware(DIContainer $container, Kernel $kernel): void
    {
        $container->singleton(RouteRunner::class, function () use ($container, $kernel) {
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
        $container->singleton(ResponseFactory::class, function () use ($container) {
            $discovery = $container->make(Psr17FactoryDiscovery::class);
            return new ResponseFactory(
                $discovery->createResponseFactory(),
                $discovery->createStreamFactory(),
            );
        });
        $container->singleton(ResponseFactoryInterface::class, fn() => $container->make(ResponseFactory::class));
        $container->singleton(StreamFactoryInterface::class, fn() => $container->make(ResponseFactory::class));
    }

    private function bindPsr17Discovery(DIContainer $container): void
    {
        // Allow using other psr implementations.
        if ($container->has(Psr17FactoryDiscovery::class)) {
            return;
        }
        $container->singleton(Psr17FactoryDiscovery::class, function (): Psr17FactoryDiscovery {
            return new Psr17FactoryDiscovery();
        });
    }

    private function bindServerRequestCreator(DIContainer $container): void
    {
        $container->singleton(ServerRequestCreator::class, function () use ($container): ServerRequestCreator {
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
            $config->getArray('routing.' . RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS),
            $config->getArray('routing.' . RoutingOption::MIDDLEWARE_ALIASES),
            $config->getArray('routing.' . RoutingOption::MIDDLEWARE_GROUPS),
            $config->getArray('routing.' . RoutingOption::MIDDLEWARE_PRIORITY)
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
        $container->singleton(Routes::class, fn() => $container->make(Routing::class)->routes());
    }

    private function bindLogger(Kernel $kernel): void
    {
        if ($kernel->env()->isTesting()) {
            $kernel->container()->singleton(TestLogger::class, fn() => new TestLogger());
            return;
        }

        if (!$kernel->container()->has(LoggerInterface::class)) {
            $kernel->container()->singleton(LoggerInterface::class, fn() => new NullLogger());
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
            return new $class;
        }, $config->getListOfStrings('routing.' . RoutingOption::EXCEPTION_TRANSFORMERS));

        return InformationProviderWithTransformation::fromDefaultData($identifier, ...$transformers);
    }

    private function validateConfig(WritableConfig $config): void
    {
        if (empty($config->getString('routing.' . RoutingOption::WP_ADMIN_PREFIX))) {
            throw new InvalidArgumentException(
                'routing.' . RoutingOption::WP_ADMIN_PREFIX . ' must be a non-empty string.'
            );
        }

        if (empty($config->getString('routing.' . RoutingOption::WP_LOGIN_PATH))) {
            throw new InvalidArgumentException(
                'routing.' . RoutingOption::WP_LOGIN_PATH . ' must be a non-empty string.'
            );
        }

        foreach ($config->getListOfStrings('routing.' . RoutingOption::ROUTE_DIRECTORIES) as $dir) {
            if (!is_readable($dir)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'routing.' . RoutingOption::ROUTE_DIRECTORIES . " must be a list of readable directories.\nPath [%s] is not readable.",
                        $dir
                    )
                );
            }
        }

        foreach ($config->getListOfStrings('routing.' . RoutingOption::API_ROUTE_DIRECTORIES) as $dir) {
            if (!is_readable($dir)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'routing.' . RoutingOption::ROUTE_DIRECTORIES . " must be a list of readable directories.\nPath [%s] is not readable.",
                        $dir
                    )
                );
            }
        }

        foreach ($config->getArray('routing.' . RoutingOption::MIDDLEWARE_GROUPS) as $key => $middleware) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::MIDDLEWARE_GROUPS . " has to an associative array of string => array pairs.\nGot key [$key]."
                );
            }
            if (!is_array($middleware)) {
                $type = gettype($middleware);
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::MIDDLEWARE_GROUPS . " has to an associative array of string => array pairs.\nGot [$type] for key [$key]."
                );
            }

            /**
             * @psalm-suppress MixedAssignment
             */
            foreach ($middleware as $index => $m) {
                if (!is_string($m)) {
                    $type = gettype($m);
                    throw new InvalidArgumentException(
                        "Middleware group [$key] has to contain only strings.\nGot [$type] at index [$index]."
                    );
                }
            }
        }

        foreach ($config->getArray('routing.' . RoutingOption::MIDDLEWARE_ALIASES) as $alias => $class) {
            if (!is_string($alias)) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::MIDDLEWARE_ALIASES . ' has to be an array of string => middleware-class pairs.'
                );
            }
            if (!is_string($class)
                || !class_exists($class)
                || !in_array(
                    MiddlewareInterface::class,
                    (array)class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    "Middleware alias [$alias] has to resolve to a middleware class."
                );
            }
        }

        foreach ($config->getListOfStrings('routing.' . RoutingOption::MIDDLEWARE_PRIORITY) as $class) {
            if (
                !class_exists($class)
                || !in_array(
                    MiddlewareInterface::class,
                    (array)class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::MIDDLEWARE_PRIORITY . " has to be a list of middleware class-strings.\nGot [$class]."
                );
            }
        }

        $valid = [
            RoutingConfigurator::FRONTEND_MIDDLEWARE,
            RoutingConfigurator::ADMIN_MIDDLEWARE,
            RoutingConfigurator::API_MIDDLEWARE,
            RoutingConfigurator::GLOBAL_MIDDLEWARE
        ];

        foreach ($config->getListOfStrings('routing.' . RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS) as $group_name) {
            if (!in_array($group_name, $valid, true)) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS . " can only contain [frontend,api,admin,global].\nGot [$group_name]."
                );
            }
        }

        $config->getInteger('routing.' . RoutingOption::HTTP_PORT);
        $config->getInteger('routing.' . RoutingOption::HTTPS_PORT);
        $config->getBoolean('routing.' . RoutingOption::HTTPS);

        foreach ($config->getListOfStrings('routing.' . RoutingOption::EXCEPTION_DISPLAYERS) as $class) {
            if (
                !class_exists($class)
                || !in_array(
                    ExceptionDisplayer::class,
                    (array)class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::EXCEPTION_DISPLAYERS . ' has to be a list of class-strings implementing ' . ExceptionDisplayer::class . ".\nGot [$class]."
                );
            }
        }

        foreach ($config->getListOfStrings('routing.' . RoutingOption::EXCEPTION_TRANSFORMERS) as $class) {
            if (
                !class_exists($class)
                || !in_array(
                    ExceptionTransformer::class,
                    (array)class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::EXCEPTION_TRANSFORMERS . ' has to be a list of class-strings implementing ' . ExceptionTransformer::class . ".\nGot [$class]."
                );
            }
        }

        foreach ($config->getListOfStrings('routing.' . RoutingOption::EXCEPTION_REQUEST_CONTEXT) as $class) {
            if (
                !class_exists($class)
                || !in_array(
                    RequestLogContext::class,
                    (array)class_implements($class)
                )) {
                throw new InvalidArgumentException(
                    'routing.' . RoutingOption::EXCEPTION_REQUEST_CONTEXT . ' has to be a list of class-strings implementing ' . RequestLogContext::class . ".\nGot [$class]."
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
            LogLevel::DEBUG
        ];

        foreach ($config->getArray('routing.' . RoutingOption::EXCEPTION_LOG_LEVELS) as $class => $level) {
            if (!is_string($class)
                || !class_exists($class)
                || !in_array(
                    Throwable::class,
                    (array)class_implements($class)
                )) {
                $class = (string)$class;
                throw new InvalidArgumentException(
                    "[$class] is not a valid exception class-string for " . RoutingOption::EXCEPTION_LOG_LEVELS
                );
            }

            if (!is_string($level) || !in_array($level, $valid_levels, true)) {
                $level = (string)$level;

                throw new InvalidArgumentException(
                    sprintf(
                        "[$level] is not a valid PSR-3 log-level for exception class " . $class . "\nValid levels: [%s]",
                        implode(',', $valid_levels)
                    )
                );
            }
        }
    }
}