<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing;

use Closure;
use PHPUnit\Framework\TestCase;
use Pimple\Psr11\Container;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

final class RoutingTest extends TestCase
{

    private Routing $routing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routing = $this->createRoutingFacade(function (RoutingConfigurator $configurator) {
            return new PHPFileRouteLoader(
                $configurator,
                new DefaultRouteLoadingOptions('/snicco')
            );
        });
    }

    /**
     * @test
     */
    public function test_web_routing_configurator(): void
    {
        $r1 = $this->routing->webConfigurator();
        $this->assertInstanceOf(WebRoutingConfigurator::class, $r1);
        $this->assertSame($r1, $this->routing->webConfigurator());
    }

    /**
     * @test
     */
    public function test_admin_routing_configurator(): void
    {
        $r1 = $this->routing->adminConfigurator();
        $this->assertInstanceOf(AdminRoutingConfigurator::class, $r1);
        $this->assertSame($r1, $this->routing->adminConfigurator());
    }

    /**
     * @test
     */
    public function test_urlMatcher(): void
    {
        $r1 = $this->routing->urlMatcher();
        $this->assertInstanceOf(UrlMatcher::class, $r1);
        $this->assertSame($r1, $this->routing->urlMatcher());
    }

    /**
     * @test
     */
    public function test_urlGenerator(): void
    {
        $r1 = $this->routing->urlGenerator();
        $this->assertInstanceOf(UrlGeneratorInterface::class, $r1);
        $this->assertSame($r1, $this->routing->urlGenerator());
    }

    /**
     * @test
     */
    public function test_routes(): void
    {
        $r1 = $this->routing->routes();
        $this->assertInstanceOf(Routes::class, $r1);
        $this->assertSame($r1, $this->routing->routes());
    }

    /**
     * @test
     */
    public function test_routeLoader(): void
    {
        $r1 = $this->routing->routeLoader();
        $this->assertInstanceOf(RouteLoader::class, $r1);
        $this->assertSame($r1, $this->routing->routeLoader());
    }

    /**
     * @test
     */
    public function test_admin_menu(): void
    {
        $admin_menu = $this->routing->adminMenu();
        $this->assertInstanceOf(AdminMenu::class, $admin_menu);
        $this->assertSame($admin_menu, $this->routing->adminMenu());
    }

    /**
     * @test
     * @psalm-suppress DocblockTypeContradiction
     */
    public function routes_are_loaded_automatically_when_the_url_matcher_is_resolved(): void
    {
        $spy_matcher = new class implements RouteLoader {

            public int $api_load_count = 0;
            public int $default_load_count = 0;

            public function loadRoutesIn(array $directories): void
            {
                $this->default_load_count++;
            }

            public function loadApiRoutesIn(array $directories): void
            {
                $this->api_load_count++;
            }
        };

        $routing = $this->createRoutingFacade(function () use ($spy_matcher) {
            return $spy_matcher;
        });

        $this->assertSame(0, $spy_matcher->api_load_count);
        $this->assertSame(0, $spy_matcher->default_load_count);

        $routing->urlMatcher();

        $this->assertSame(1, $spy_matcher->api_load_count);
        $this->assertSame(1, $spy_matcher->default_load_count);

        $routing->urlMatcher();

        $this->assertSame(1, $spy_matcher->api_load_count);
        $this->assertSame(1, $spy_matcher->default_load_count);
    }

    /**
     * @test
     * @psalm-suppress DocblockTypeContradiction
     */
    public function routes_are_loaded_automatically_when_the_url_generator_is_resolved(): void
    {
        $spy_matcher = new class implements RouteLoader {

            public int $api_load_count = 0;
            public int $default_load_count = 0;

            public function loadRoutesIn(array $directories): void
            {
                $this->default_load_count++;
            }

            public function loadApiRoutesIn(array $directories): void
            {
                $this->api_load_count++;
            }
        };

        $routing = $this->createRoutingFacade(function () use ($spy_matcher) {
            return $spy_matcher;
        });

        $this->assertSame(0, $spy_matcher->api_load_count);
        $this->assertSame(0, $spy_matcher->default_load_count);

        $routing->urlGenerator();

        $this->assertSame(1, $spy_matcher->api_load_count);
        $this->assertSame(1, $spy_matcher->default_load_count);

        $routing->urlGenerator();

        $this->assertSame(1, $spy_matcher->api_load_count);
        $this->assertSame(1, $spy_matcher->default_load_count);

        $routing->urlMatcher();

        $this->assertSame(1, $spy_matcher->api_load_count);
        $this->assertSame(1, $spy_matcher->default_load_count);
    }


    /**
     * @param Closure(RoutingConfigurator):RouteLoader $loader
     * @param string[] $route_dir
     * @param string[] $api_route_dir
     */
    private function createRoutingFacade(Closure $loader, array $route_dir = [], array $api_route_dir = []): Routing
    {
        return new Routing(
            new Container(new \Pimple\Container()),
            UrlGenerationContext::forConsole('127.0.0.0'),
            $loader,
        );
    }

}