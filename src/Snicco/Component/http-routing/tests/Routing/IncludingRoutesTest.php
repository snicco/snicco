<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

final class IncludingRoutesTest extends HttpRunnerTestCase
{
    
    /** @test */
    public function test_exception_if_no_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string or a closure');
        
        $this->routeConfigurator()->include(1);
    }
    
    /** @test */
    public function test_exception_if_unreadable_file()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not readable');
        
        $this->routeConfigurator()->include($this->routes_dir.'/bogus.php');
    }
    
    /** @test */
    public function test_exception_if_no_closure_returned()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has to return a closure');
        
        $this->routeConfigurator()->include($this->routes_dir.'/_no_closure.php');
    }
    
    /** @test */
    public function routes_can_be_included_as_a_string()
    {
        $this->withMiddlewareAlias(['partial' => FooMiddleware::class]);
        $this->routeConfigurator()->include($this->routes_dir.'/_partial.php');
        
        $this->assertResponseBody(
            RoutingTestController::static.':foo_middleware',
            $this->frontendRequest(RouteLoaderTest::PARTIAL_PATH)
        );
    }
    
    /** @test */
    public function routes_can_be_included_as_a_closure()
    {
        $this->withMiddlewareAlias(['partial' => FooMiddleware::class]);
        
        $closure = require $this->routes_dir.'/_partial.php';
        
        $this->routeConfigurator()->include($closure);
        
        $this->assertResponseBody(
            RoutingTestController::static.':foo_middleware',
            $this->frontendRequest(RouteLoaderTest::PARTIAL_PATH)
        );
    }
    
}