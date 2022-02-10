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
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlEncoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteSyntaxConverter;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

use function call_user_func;

final class Routing
{

    private ContainerInterface $psr_container;
    private UrlGenerationContext $context;
    private AdminArea $admin_area;
    private UrlEncoder $url_encoder;

    /**
     * @var callable(RoutingConfigurator\RoutingConfigurator):RouteLoader
     */
    private $route_loader_factory;

    private ?RoutingConfiguratorUsingRouter $routing_configurator = null;
    private ?Router $router = null;
    private ?RouteLoader $route_loader = null;

    /**
     * @param callable(RoutingConfigurator\RoutingConfigurator):RouteLoader $route_loader_factory
     */
    public function __construct(
        ContainerInterface $psr_container,
        UrlGenerationContext $context,
        callable $route_loader_factory,
        ?AdminArea $admin_area = null,
        ?UrlEncoder $url_encoder = null
    ) {
        $this->psr_container = $psr_container;
        $this->context = $context;
        $this->route_loader_factory = $route_loader_factory;
        $this->admin_area = $admin_area ?: WPAdminArea::fromDefaults();
        $this->url_encoder = $url_encoder ?: new RFC3986Encoder();
    }

    public function webConfigurator(): WebRoutingConfigurator
    {
        return $this->routingConfigurator();
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

    public function adminConfigurator(): AdminRoutingConfigurator
    {
        return $this->routingConfigurator();
    }

    public function routeLoader(): RouteLoader
    {
        if (!isset($this->route_loader)) {
            $this->route_loader = call_user_func($this->route_loader_factory, $this->routingConfigurator());
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
                new NullCache(),
            );
        }
        return $this->router;
    }

    private function fastRouteData()
    {
        return function (Routes $routes) {
            $collector = new RouteCollector(new RouteParser(), new DataGenerator());
            $syntax = new FastRouteSyntaxConverter();

            foreach ($routes as $route) {
                $path = $syntax->convert($route);
                try {
                    $collector->addRoute($route->getMethods(), $path, $route->getName());
                } catch (BadRouteException $e) {
                    throw BadRouteConfiguration::fromPrevious($e);
                }
            }

            return $collector->getData();
        };
    }

    private function routingConfigurator(): RoutingConfiguratorUsingRouter
    {
        if (!isset($this->routing_configurator)) {
            $this->routing_configurator = new RoutingConfiguratorUsingRouter(
                $this->router(),
                $this->admin_area->urlPrefix(),
            );
        }
        return $this->routing_configurator;
    }

}