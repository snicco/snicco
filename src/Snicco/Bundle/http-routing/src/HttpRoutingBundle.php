<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting;

use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Snicco\Bundle\HttpRouting\ErrorHandler\DisplayerCollection;
use Snicco\Bundle\HttpRouting\ErrorHandler\ExceptionTransformerCollection;
use Snicco\Bundle\HttpRouting\ErrorHandler\NullErrorHandler;
use Snicco\Bundle\HttpRouting\ErrorHandler\RequestLogContextCollection;
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
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Delegating;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Filter;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProvider;
use Snicco\Component\Psr7ErrorHandler\Information\TransformableInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;

final class HttpRoutingBundle implements Bundle
{

    public const ALIAS = 'sniccowp/http-routing-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        if (!$config->has('routing.host')) {
            throw new InvalidArgumentException('routing.host must be a non-empty-string.');
        }
        $config->extend('routing.' . RoutingOption::WP_ADMIN_PREFIX, '/wp-admin');
        $config->extend('routing.' . RoutingOption::WP_LOGIN_PATH, '/wp-login.php');
        $config->extend('routing.' . RoutingOption::ROUTE_DIRECTORIES, []);
        $config->extend('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, []);
        $config->extend('routing.' . RoutingOption::API_PREFIX, '');
        $config->extend('routing.' . RoutingOption::MIDDLEWARE_GROUPS, []);
        $config->extend('routing.' . RoutingOption::MIDDLEWARE_ALIASES, []);
        $config->extend('routing.' . RoutingOption::MIDDLEWARE_PRIORITY, []);
        $config->extend('routing.' . RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS, []);
        $config->extend('routing.' . RoutingOption::HTTP_PORT, 80);
        $config->extend('routing.' . RoutingOption::HTTPS_PORT, 443);
        $config->extend('routing.' . RoutingOption::HTTPS, true);
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
                $config->getString('routing.host'),
                $config->getInteger('routing.https_port'),
                $config->getInteger('routing.http_port'),
                $config->getBoolean('routing.https')
            );

            $loader = $container[RouteLoader::class] ?? new PHPFileRouteLoader(
                    $config->getListOfStrings('routing.route_directories'),
                    $config->getListOfStrings('routing.api_route_directories'),
                    $container[RouteLoadingOptions::class] ?? new DefaultRouteLoadingOptions(
                        $config->getString('routing.api_prefix')
                    ),
                );

            $cache = ($env->isStaging() || $env->isProduction())
                ? new FileRouteCache(
                    $kernel->directories()->cacheDir() . '/prod.routes-generated.php'
                )
                : new NullCache();

            $admin_area = new WPAdminArea(
                $config->getString('routing.wp_admin_prefix'),
                $config->getString('routing.wp_login_path')
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
        $container->singleton(
            UrlGenerator::class,
            fn() => $container->make(Routing::class)->urlGenerator()
        );
    }

    private function bindUrlMatcher(DIContainer $container): void
    {
        $container->singleton(
            UrlMatcher::class,
            fn() => $container->make(Routing::class)->urlMatcher()
        );
    }

    private function bindAdminMenu(DIContainer $container): void
    {
        $container->singleton(
            AdminMenu::class,
            fn() => $container->make(Routing::class)->adminMenu()
        );
    }

    private function bindErrorHandler(DIContainer $container, Kernel $kernel): void
    {
        $container->singleton(DisplayerCollection::class, function () {
            return new DisplayerCollection();
        });
        $container->singleton(ExceptionTransformerCollection::class, function () {
            return new ExceptionTransformerCollection();
        });
        $container->singleton(RequestLogContextCollection::class, function () {
            return new RequestLogContextCollection();
        });
        $container->singleton(
            HttpErrorHandlerInterface::class,
            function () use ($container, $kernel): HttpErrorHandlerInterface {
                $null_handler = $container[NullErrorHandler::class] ?? null;

                if ($null_handler) {
                    return $null_handler;
                }

                /** @var LoggerInterface $psr_logger */
                $psr_logger = $container[LoggerInterface::class] ?? new NullLogger();

                $information_provider = $container[InformationProvider::class] ?? TransformableInformationProvider::withDefaultData(
                        new SplHashIdentifier(),
                        ...$container->make(ExceptionTransformerCollection::class)->all()
                    );

                $displayer_filter = $container[Filter::class] ?? new Delegating(
                        new ContentType(),
                        new Verbosity($kernel->env()->isDebug()),
                        new CanDisplay(),
                    );

                $logger = new RequestAwareLogger(
                    $psr_logger,
                    [],
                    ...$container->make(RequestLogContextCollection::class)->all()
                );

                return new HttpErrorHandler(
                    $container->make(ResponseFactoryInterface::class),
                    $logger,
                    $information_provider,
                    $container[ExceptionDisplayer::class] ?? new FallbackDisplayer(),
                    $displayer_filter,
                    $container->make(DisplayerCollection::class)->getIterator()
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

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function bindRouteRunnerMiddleware(DIContainer $container, Kernel $kernel): void
    {
        $container->singleton(RouteRunner::class, function () use ($container, $kernel) {
            $config = $kernel->config();
            $middleware_resolver = new MiddlewareResolver(
                $config->getArray('routing.always_run_middleware_groups'),
                $config->getArray('routing.middleware_aliases'),
                $config->getArray('routing.middleware_groups'),
                $config->getArray('routing.middleware_priority')
            );

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
}