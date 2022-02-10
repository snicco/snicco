<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Pimple\Psr11\Container;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
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
        $this->routing = new Routing(
            new Container(new \Pimple\Container()),
            UrlGenerationContext::forConsole('127.0.0.0'),
            new DefaultRouteLoadingOptions('/snicco')
        );
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

}