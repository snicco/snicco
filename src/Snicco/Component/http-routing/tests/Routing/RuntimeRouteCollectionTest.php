<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RuntimeRouteCollection;

final class RuntimeRouteCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function test_count(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RuntimeRouteCollection();
        $routes->add($r1);
        $routes->add($r2);

        $this->assertSame(2, count($routes));
    }

    /**
     * @test
     */
    public function test_iterator(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RuntimeRouteCollection();
        $routes->add($r1);
        $routes->add($r2);

        $count = 0;
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $count++;
        }
        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function test_exception_for_bad_route_name(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RuntimeRouteCollection();
        $routes->add($r1);
        $routes->add($r2);

        $route = $routes->getByName('r1');
        $this->assertEquals($r1, $route);

        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('r3');
        $routes->getByName('r3');
    }

}