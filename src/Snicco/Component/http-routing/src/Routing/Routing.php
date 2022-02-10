<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing;

use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlEncoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\ValueObject\PHPCacheFile;

final class Routing
{

    private ContainerInterface $psr_container;
    private UrlGenerationContext $context;
    private ?PHPCacheFile $cache_file;
    private AdminArea $admin_area;
    private UrlEncoder $url_encoder;
    private RouteLoadingOptions $route_loading_options;

    private ?RoutingConfiguratorUsingRouter $routing_configurator = null;
    private ?UrlMatcher $url_matcher = null;
    private ?Router $router = null;
    private ?PHPFileRouteLoader $route_loader = null;

    public function __construct(
        ContainerInterface $psr_container,
        UrlGenerationContext $context,
        RouteLoadingOptions $route_loading_options,
        ?PHPCacheFile $cache_file = null,
        ?AdminArea $admin_area = null,
        ?UrlEncoder $url_encoder = null
    ) {
        $this->psr_container = $psr_container;
        $this->context = $context;
        $this->route_loading_options = $route_loading_options;
        $this->admin_area = $admin_area ?: WPAdminArea::fromDefaults();
        $this->url_encoder = $url_encoder ?: new RFC3986Encoder();
        $this->cache_file = $cache_file;
    }

    public function webConfigurator(): WebRoutingConfigurator
    {
        return $this->routingConfigurator();
    }

    public function urlMatcher(): UrlMatcher
    {
        if (!isset($this->url_matcher)) {
            $this->url_matcher = $this->router();
        }
        return $this->url_matcher;
    }

    public function urlGenerator(): UrlGeneratorInterface
    {
        return $this->router();
    }

    public function routes(): Routes
    {
        return $this->router();
    }

    public function adminConfigurator(): AdminRoutingConfigurator
    {
        return $this->routingConfigurator();
    }

    public function routeLoader(): RouteLoader
    {
        if (!isset($this->route_loader)) {
            $this->route_loader = new PHPFileRouteLoader(
                $this->routingConfigurator(),
                $this->route_loading_options
            );
        }
        return $this->route_loader;
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

            $this->router = new Router(
                $condition_factory,
                function (Routes $routes) {
                    return new UrlGenerator(
                        $routes,
                        $this->context,
                        $this->admin_area,
                        $this->url_encoder
                    );
                },
                $this->admin_area,
                $this->cache_file,
            );
        }
        return $this->router;
    }

    private function routingConfigurator(): RoutingConfiguratorUsingRouter
    {
        if (!isset($this->routing_configurator)) {
            $this->routing_configurator = new RoutingConfiguratorUsingRouter(
                $this->router(),
                $this->admin_area->urlPrefix(),
                []
            );
        }
        return $this->routing_configurator;
    }

}