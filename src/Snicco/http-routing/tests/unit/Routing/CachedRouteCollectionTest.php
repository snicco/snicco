<?php

declare(strict_types=1);

namespace Tests\HttpRouting\unit\Routing;

use stdClass;
use LogicException;
use InvalidArgumentException;
use Tests\Codeception\shared\UnitTest;
use Snicco\HttpRouting\Routing\Route\Route;
use Snicco\HttpRouting\Routing\Route\CachedRouteCollection;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

final class CachedRouteCollectionTest extends UnitTest
{
    
    /** @test */
    public function test_exception_if_value_is_not_a_serialized_string()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can only contain serialized routes');
        
        $routes = new CachedRouteCollection(['r1' => $r1]);
    }
    
    /** @test */
    public function test_exception_when_iterator_contains_non_route()
    {
        $data = ['r1' => serialize(new stdClass())];
        
        $routes = new CachedRouteCollection($data);
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Your route cache seems corrupted.\nThe cached route collection contained a serialized of type [%s].",
                stdClass::class
            )
        );
        
        iterator_to_array($routes);
    }
    
    /** @test */
    public function test_count()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new CachedRouteCollection(['r1' => serialize($r1), 'r2' => serialize($r2)]);
        
        $this->assertSame(2, count($routes));
    }
    
    /** @test */
    public function test_iterator()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new CachedRouteCollection(['r1' => serialize($r1), 'r2' => serialize($r2)]);
        
        $count = 0;
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $count++;
        }
        $this->assertSame(2, $count);
    }
    
    /** @test */
    public function test_exception_for_not_found_route_name()
    {
        $this->expectException(RouteNotFound::class);
        
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new CachedRouteCollection(['r1' => serialize($r1), 'r2' => serialize($r2)]);
        
        $route = $routes->getByName('r1');
        $this->assertEquals($r1, $route);
        
        $route = $routes->getByName('r3');
    }
    
    /** @test */
    public function test_exception_for_invalid_route_getByName()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes =
            new CachedRouteCollection(['r1' => serialize(new stdClass()), 'r2' => serialize($r2)]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Your route cache seems corrupted");
        $route = $routes->getByName('r1');
    }
    
    /** @test */
    public function test_getName_and_iterator_in_order()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new CachedRouteCollection(['r1' => serialize($r1), 'r2' => serialize($r2)]);
        
        $route = $routes->getByName('r1');
        $this->assertInstanceOf(Route::class, $route);
        
        $count = 0;
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $count++;
        }
        $this->assertSame(2, $count);
    }
    
    /** @test */
    public function test_exception_if_route_is_stored_by_different_name()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        $routes = new CachedRouteCollection(['route1' => serialize($r1), 'r2' => serialize($r2)]);
        
        try {
            iterator_to_array($routes);
            $this->fail("Expected exception for iterator_to_array on route collection.");
        } catch (RouteNotFound $e) {
            $this->assertEquals(
                "Route accessed with bad name.\nRoute with real name [r1] is stored with name [route1].",
                $e->getMessage()
            );
        }
        
        $routes = new CachedRouteCollection(['route1' => serialize($r1), 'r2' => serialize($r2)]);
        
        try {
            $n = $routes->getByName('route1');
            $this->fail("Expected exception for getByName().");
        } catch (RouteNotFound $e) {
            $this->assertEquals(
                "Route accessed with bad name.\nRoute with real name [r1] is stored with name [route1].",
                $e->getMessage()
            );
        }
    }
    
}