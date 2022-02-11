<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing;

use PHPUnit\Framework\TestCase;

final class RoutingTest extends TestCase
{

//    private Routing $routing;
//
//    protected function setUp(): void
//    {
//        parent::setUp();
//        $this->routing = $this->createRoutingFacade(function (RoutingConfigurator $configurator) {
//            return new PHPFileRouteLoader(
//                $configurator,
//                new DefaultRouteLoadingOptions('/snicco')
//            );
//        });
//    }
//
//    /**
//     * @test
//     */
//    public function test_urlMatcher(): void
//    {
//        $r1 = $this->routing->urlMatcher();
//        $this->assertInstanceOf(UrlMatcher::class, $r1);
//        $this->assertSame($r1, $this->routing->urlMatcher());
//    }
//
//    /**
//     * @test
//     */
//    public function test_urlGenerator(): void
//    {
//        $r1 = $this->routing->urlGenerator();
//        $this->assertInstanceOf(UrlGeneratorInterface::class, $r1);
//        $this->assertSame($r1, $this->routing->urlGenerator());
//    }
//
//    /**
//     * @test
//     */
//    public function test_routes(): void
//    {
//        $r1 = $this->routing->routes();
//        $this->assertInstanceOf(Routes::class, $r1);
//        $this->assertSame($r1, $this->routing->routes());
//    }
//
//    /**
//     * @test
//     */
//    public function test_admin_menu(): void
//    {
//        $admin_menu = $this->routing->adminMenu();
//        $this->assertInstanceOf(AdminMenu::class, $admin_menu);
//        $this->assertSame($admin_menu, $this->routing->adminMenu());
//    }
//
//    /**
//     * @test
//     * @psalm-suppress DocblockTypeContradiction
//     */
//    public function routes_are_loaded_automatically_when_the_url_matcher_is_resolved(): void
//    {
//        $spy_matcher = new class implements RouteLoader {
//
//            public int $api_load_count = 0;
//            public int $default_load_count = 0;
//
//            public function loadRoutesIn(array $directories): void
//            {
//                $this->default_load_count++;
//            }
//
//            public function loadApiRoutesIn(array $directories): void
//            {
//                $this->api_load_count++;
//            }
//        };
//
//        $routing = $this->createRoutingFacade(function () use ($spy_matcher) {
//            return $spy_matcher;
//        });
//
//        $this->assertSame(0, $spy_matcher->api_load_count);
//        $this->assertSame(0, $spy_matcher->default_load_count);
//
//        $routing->urlMatcher();
//
//        $this->assertSame(1, $spy_matcher->api_load_count);
//        $this->assertSame(1, $spy_matcher->default_load_count);
//
//        $routing->urlMatcher();
//
//        $this->assertSame(1, $spy_matcher->api_load_count);
//        $this->assertSame(1, $spy_matcher->default_load_count);
//    }
//
//    /**
//     * @test
//     * @psalm-suppress DocblockTypeContradiction
//     */
//    public function routes_are_loaded_automatically_when_the_url_generator_is_resolved(): void
//    {
//        $spy_matcher = new class implements RouteLoader {
//
//            public int $api_load_count = 0;
//            public int $default_load_count = 0;
//
//            public function loadRoutesIn(array $directories): void
//            {
//                $this->default_load_count++;
//            }
//
//            public function loadApiRoutesIn(array $directories): void
//            {
//                $this->api_load_count++;
//            }
//        };
//
//        $routing = $this->createRoutingFacade(function () use ($spy_matcher) {
//            return $spy_matcher;
//        });
//
//        $this->assertSame(0, $spy_matcher->api_load_count);
//        $this->assertSame(0, $spy_matcher->default_load_count);
//
//        $routing->urlGenerator();
//
//        $this->assertSame(1, $spy_matcher->api_load_count);
//        $this->assertSame(1, $spy_matcher->default_load_count);
//
//        $routing->urlGenerator();
//
//        $this->assertSame(1, $spy_matcher->api_load_count);
//        $this->assertSame(1, $spy_matcher->default_load_count);
//
//        $routing->urlMatcher();
//
//        $this->assertSame(1, $spy_matcher->api_load_count);
//        $this->assertSame(1, $spy_matcher->default_load_count);
//    }
//
//
//    /**
//     * @param Closure(RoutingConfigurator):RouteLoader $loader
//     * @param string[] $route_dir
//     * @param string[] $api_route_dir
//     */
//    private function createRoutingFacade(Closure $loader, array $route_dir = [], array $api_route_dir = []): Routing
//    {
//        return new Routing(
//            new Container(new \Pimple\Container()),
//            UrlGenerationContext::forConsole('127.0.0.0'),
//            new NullLoader()
//        );
//    }

}