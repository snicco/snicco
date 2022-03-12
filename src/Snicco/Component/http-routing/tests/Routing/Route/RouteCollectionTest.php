<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Route;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;

/**
 * @internal
 */
final class RouteCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function test_count(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RouteCollection([$r1, $r2]);

        $this->assertCount(2, $routes);
    }

    /**
     * @test
     */
    public function test_iterator(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RouteCollection([$r1, $r2]);

        $count = 0;
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            ++$count;
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

        $routes = new RouteCollection([$r1, $r2]);

        $route = $routes->getByName('r1');
        $this->assertEquals($r1, $route);

        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('r3');
        $routes->getByName('r3');
    }

    /**
     * @test
     */
    public function test_exception_for_duplicate_route(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate route name [foo].');

        $r1 = Route::create('/foo', Route::DELEGATE, 'foo');
        $r2 = Route::create('/foo2', Route::DELEGATE, 'foo');

        new RouteCollection([$r1, $r2]);
    }

    /**
     * @test
     */
    public function test_to_array(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');

        $routes = new RouteCollection([$r1, $r2]);

        $this->assertSame([
            'r1' => $r1,
            'r2' => $r2,
        ], $routes->toArray());
    }
}
