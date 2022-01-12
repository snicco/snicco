<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use stdClass;
use InvalidArgumentException;
use Snicco\Core\Routing\Route\Route;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Routing\Route\RouteCollection;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

final class RouteCollectionTest extends UnitTest
{
    
    /** @test */
    public function test_exception_if_constructed_with_bad_route()
    {
        $this->expectException(InvalidArgumentException::class);
        $routes = new RouteCollection([new stdClass()]);
    }
    
    /** @test */
    public function test_exception_if_route_with_duplicate_name_added()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf("Duplicate route with name [r1] while create [%s].", RouteCollection::class)
        );
        
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r1');
        
        $routes = new RouteCollection([$r1, $r2]);
    }
    
    /** @test */
    public function test_count()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        
        $routes = new RouteCollection([$r1, $r2]);
        
        $this->assertSame(2, count($routes));
    }
    
    /** @test */
    public function test_iterator()
    {
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        
        $routes = new RouteCollection([$r1, $r2]);
        
        $count = 0;
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $count++;
        }
        $this->assertSame(2, $count);
    }
    
    /** @test */
    public function test_exception_for_bad_route_name()
    {
        $this->expectException(RouteNotFound::class);
        
        $r1 = Route::create('/foo', Route::DELEGATE, 'r1');
        $r2 = Route::create('/bar', Route::DELEGATE, 'r2');
        
        $routes = new RouteCollection([$r1, $r2]);
        
        $route = $routes->getByName('r1');
        $this->assertEquals($r1, $route);
        
        $route = $routes->getByName('r3');
    }
    
}