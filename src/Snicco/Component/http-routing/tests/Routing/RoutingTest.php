<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

use function dirname;

final class RoutingTest extends TestCase
{

    /**
     * @test
     */
    public function test_url_matcher_is_singleton(): void
    {
        $routing = new Routing(
            new \Pimple\Psr11\Container(new Container()),
            UrlGenerationContext::forConsole('127.0.0.1'),
            new PHPFileRouteLoader(
                [dirname(__DIR__) . '/fixtures/routes'],
                [],
                new DefaultRouteLoadingOptions(''),
            ),
        );

        $url_matcher = $routing->urlMatcher();

        $this->assertInstanceOf(UrlMatcher::class, $url_matcher);
        $this->assertSame($url_matcher, $routing->urlMatcher());
    }

    /**
     * @test
     */
    public function test_url_generator_is_singleton(): void
    {
        $routing = new Routing(
            new \Pimple\Psr11\Container(new Container()),
            UrlGenerationContext::forConsole('127.0.0.1'),
            new PHPFileRouteLoader(
                [dirname(__DIR__) . '/fixtures/routes'],
                [],
                new DefaultRouteLoadingOptions(''),
            ),
        );

        $url_matcher = $routing->urlGenerator();

        $this->assertInstanceOf(UrlGenerator::class, $url_matcher);
        $this->assertSame($url_matcher, $routing->urlGenerator());
    }

    /**
     * @test
     */
    public function test_routes_is_singleton(): void
    {
        $routing = new Routing(
            new \Pimple\Psr11\Container(new Container()),
            UrlGenerationContext::forConsole('127.0.0.1'),
            new PHPFileRouteLoader(
                [dirname(__DIR__) . '/fixtures/routes'],
                [],
                new DefaultRouteLoadingOptions(''),
            ),
        );

        $routes = $routing->routes();

        $this->assertInstanceOf(Routes::class, $routes);
        $this->assertSame($routes, $routing->routes());
    }

    /**
     * @test
     */
    public function test_admin_menu_is_singleton(): void
    {
        $routing = new Routing(
            new \Pimple\Psr11\Container(new Container()),
            UrlGenerationContext::forConsole('127.0.0.1'),
            new PHPFileRouteLoader(
                [dirname(__DIR__) . '/fixtures/routes'],
                [],
                new DefaultRouteLoadingOptions(''),
            ),
        );

        $admin_menu = $routing->adminMenu();

        $this->assertInstanceOf(AdminMenu::class, $admin_menu);
        $this->assertSame($admin_menu, $routing->adminMenu());
    }

}