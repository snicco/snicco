<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Cache\NullCache;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\CachedRouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlEncoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteSyntaxConverter;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

use function serialize;

final class Routing
{

    private ContainerInterface $psr_container;
    private UrlGenerationContext $context;
    private AdminArea $admin_area;
    private UrlEncoder $url_encoder;
    private ?RoutingConfiguratorUsingRouter $routing_configurator = null;
    private ?Router $router = null;
    private RouteLoader $route_loader;

    /**
     * @param callable(RoutingConfigurator):RouteLoader $route_loader_factory
     */
    public function __construct(
        ContainerInterface $psr_container,
        UrlGenerationContext $context,
        RouteLoader $loader,
        ?AdminArea $admin_area = null,
        ?UrlEncoder $url_encoder = null
    ) {
        $this->psr_container = $psr_container;
        $this->context = $context;
        $this->route_loader = $loader;
        $this->admin_area = $admin_area ?: WPAdminArea::fromDefaults();
        $this->url_encoder = $url_encoder ?: new RFC3986Encoder();
    }

    public function urlMatcher(): UrlMatcher
    {
        return $this->router();
    }

    public function urlGenerator(): UrlGeneratorInterface
    {
        return $this->router();
    }

    public function routes(): Routes
    {
        return $this->router();
    }

    public function adminMenu(): AdminMenu
    {
        return $this->routingConfigurator();
    }


    private function router(): Router
    {
        if (!isset($this->router)) {
            $condition_factory = new RouteConditionFactory(
                $this->psr_container
            );

            $cache = new NullCache();

            $data = $cache->get('foo', function () {
                $configurator = $this->routingConfigurator();
                $this->route_loader->loadWebRoutes($configurator);
                $this->route_loader->loadAdminRoutes($configurator);

                $routes = $configurator->configuredRoutes();

                $collector = new RouteCollector(new RouteParser(), new DataGenerator());
                $syntax = new FastRouteSyntaxConverter();

                $collection = [];

                foreach ($routes as $route) {
                    $collection[$route->getName()] = serialize($route);
                    $path = $syntax->convert($route);
                    try {
                        $collector->addRoute($route->getMethods(), $path, $route->getName());
                    } catch (BadRouteException $e) {
                        throw BadRouteConfiguration::fromPrevious($e);
                    }
                }

                return [
                    'route_collection' => $collection,
                    'fast_route' => $collector->getData()
                ];
            });

            $this->router = new Router(
                $routes = new CachedRouteCollection($data['route_collection']),
                function (Routes $routes) {
                    return new UrlGenerator(
                        $routes,
                        $this->context,
                        $this->admin_area,
                        $this->url_encoder,
                    );
                },
                $this->admin_area,
                new FastRouteDispatcher(
                    $routes,
                    $data['fast_route'],
                    $condition_factory,
                )
            );
        }
        return $this->router;
    }

    private function routingConfigurator(): RoutingConfiguratorUsingRouter
    {
        if (!isset($this->routing_configurator)) {
            $this->routing_configurator = new RoutingConfiguratorUsingRouter(
                $this->admin_area->urlPrefix(),
            );
        }
        return $this->routing_configurator;
    }

}