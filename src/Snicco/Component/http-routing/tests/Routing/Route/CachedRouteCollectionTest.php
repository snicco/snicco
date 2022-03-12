<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Route;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\SerializedRouteCollection;
use stdClass;

use function serialize;

/**
 * @internal
 */
final class CachedRouteCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function test_exception_when_iterator_contains_non_route(): void
    {
        $data = [
            'r1' => serialize(new stdClass()),
        ];

        $routes = new SerializedRouteCollection($data);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Your route cache seems corrupted.\nThe cached route collection contained a serialized of type [%s].",
                stdClass::class
            )
        );

        iterator_to_array($routes);
    }

    /**
     * @test
     */
    public function test_count(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'r1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        $this->assertCount(2, $routes);
    }

    /**
     * @test
     */
    public function test_iterator(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'r1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

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
    public function test_to_array(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'r1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        $this->assertEquals([
            'r1' => $r1,
            'r2' => $r2,
        ], $routes->toArray());

        // Two times to proof that hydration works correctly for multiple calls.
        $this->assertEquals([
            'r1' => $r1,
            'r2' => $r2,
        ], $routes->toArray());
    }

    /**
     * @test
     */
    public function test_exception_for_not_found_route_name(): void
    {
        $this->expectException(RouteNotFound::class);

        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'r1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        $route = $routes->getByName('r1');
        $this->assertEquals($r1, $route);

        $routes->getByName('r3');
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_route_get_by_name(): void
    {
        Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes =
            new SerializedRouteCollection([
                'r1' => serialize(new stdClass()),
                'r2' => serialize($r2),
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Your route cache seems corrupted');
        $routes->getByName('r1');
    }

    /**
     * @test
     */
    public function test_get_name_and_iterator_in_order(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'r1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        $route = $routes->getByName('r1');
        $this->assertInstanceOf(Route::class, $route);

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
    public function test_exception_if_route_is_stored_by_different_name(): void
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new SerializedRouteCollection([
            'route1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        try {
            iterator_to_array($routes);
            $this->fail('Expected exception for iterator_to_array on route collection.');
        } catch (RouteNotFound $e) {
            $this->assertSame(
                "Route accessed with bad name.\nRoute with real name [r1] is stored with name [route1].",
                $e->getMessage()
            );
        }

        $routes = new SerializedRouteCollection([
            'route1' => serialize($r1),
            'r2' => serialize($r2),
        ]);

        try {
            $routes->getByName('route1');
            $this->fail('Expected exception for getByName().');
        } catch (RouteNotFound $e) {
            $this->assertSame(
                "Route accessed with bad name.\nRoute with real name [r1] is stored with name [route1].",
                $e->getMessage()
            );
        }
    }
}
