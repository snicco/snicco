<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\CachedAdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Cache\NullCache;
use Snicco\Component\HttpRouting\Routing\Cache\RouteCache;
use Snicco\Component\HttpRouting\Routing\Condition\NewableRouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\Route\SerializedRouteCollection;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\Configurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\LazyGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlEncoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\AdminRouteMatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\FastRouteSyntaxConverter;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

use function serialize;

/**
 * The Router class is a facade that glues together all parts of the Routing
 * system.
 */
final class Router
{
    private UrlGenerationContext $context;

    private AdminArea $admin_area;

    private UrlEncoder $url_encoder;

    private RouteCache $route_cache;

    private RouteLoader $route_loader;

    private RouteConditionFactory $condition_factory;

    private ?Configurator $routing_configurator = null;

    private ?UrlMatcher $url_matcher = null;

    private ?UrlGenerator $url_generator = null;

    private ?Routes $routes = null;

    private ?AdminMenu $admin_menu = null;

    /**
     * @var ?array{url_matcher:array, route_collection:array<string,string>, admin_menu: array<string>}
     */
    private ?array $route_data = null;

    public function __construct(
        UrlGenerationContext $context,
        RouteLoader $loader,
        ?RouteCache $cache = null,
        ?AdminArea $admin_area = null,
        ?UrlEncoder $url_encoder = null,
        RouteConditionFactory $condition_factory = null
    ) {
        $this->context = $context;
        $this->route_loader = $loader;
        $this->admin_area = $admin_area ?: WPAdminArea::fromDefaults();
        $this->url_encoder = $url_encoder ?: new RFC3986Encoder();
        $this->route_cache = $cache ?: new NullCache();
        $this->condition_factory = $condition_factory ?: new NewableRouteConditionFactory();
    }

    public function urlMatcher(): UrlMatcher
    {
        if (! isset($this->url_matcher)) {
            $this->url_matcher = new AdminRouteMatcher(
                new FastRouteDispatcher(
                    $this->routes(),
                    $this->routeData()['url_matcher'],
                    $this->condition_factory,
                ),
                $this->admin_area
            );
        }

        return $this->url_matcher;
    }

    public function urlGenerator(): UrlGenerator
    {
        if (! isset($this->url_generator)) {
            $this->url_generator = new LazyGenerator(fn (): Generator => new Generator(
                $this->routes(),
                $this->context,
                $this->admin_area,
                $this->url_encoder,
            ));
        }

        return $this->url_generator;
    }

    public function routes(): Routes
    {
        if (! isset($this->routes)) {
            $this->routes = new SerializedRouteCollection($this->routeData()['route_collection']);
        }

        return $this->routes;
    }

    public function adminMenu(): AdminMenu
    {
        if (! isset($this->admin_menu)) {
            $this->admin_menu = new CachedAdminMenu($this->routeData()['admin_menu']);
        }

        return $this->admin_menu;
    }

    private function routingConfigurator(): Configurator
    {
        if (! isset($this->routing_configurator)) {
            $this->routing_configurator = new Configurator($this->admin_area->urlPrefix(),);
        }

        return $this->routing_configurator;
    }

    /**
     * @return array{url_matcher: array, route_collection: array<string,string>, admin_menu: array<string>}
     */
    private function routeData(): array
    {
        if (! isset($this->route_data)) {
            $data = $this->route_cache->get(fn (): array => $this->loadRoutes());

            $this->route_data = $data;
        }

        return $this->route_data;
    }

    /**
     * @return array{url_matcher: array, route_collection: array<string,string>, admin_menu: array<string>}
     */
    private function loadRoutes(): array
    {
        $configurator = $this->routingConfigurator();
        $this->route_loader->loadWebRoutes($configurator);
        $this->route_loader->loadAdminRoutes($configurator);

        $routes = $configurator->configuredRoutes();

        $collector = new RouteCollector(new RouteParser(), new DataGenerator());
        $syntax = new FastRouteSyntaxConverter();

        $serialized_routes = [];

        foreach ($routes as $route) {
            $serialized_routes[$route->getName()] = serialize($route);
            $path = $syntax->convert($route);

            try {
                $collector->addRoute($route->getMethods(), $path, $route->getName());
            } catch (BadRouteException $e) {
                throw BadRouteConfiguration::fromPrevious($e);
            }
        }

        $menu = [];

        foreach ($configurator->items() as $admin_menu_item) {
            $menu[] = serialize($admin_menu_item);
        }

        return [
            'route_collection' => $serialized_routes,
            'url_matcher' => $collector->getData(),
            'admin_menu' => $menu,
        ];
    }
}
