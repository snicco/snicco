<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Tests\RoutingTestCase;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

class TrailingSlashesTest extends RoutingTestCase
{
    
    /** @test */
    public function routes_can_be_defined_without_leading_slash()
    {
        $this->routeConfigurator()->get('foo_route', 'foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function routes_can_be_defined_with_leading_slash()
    {
        $this->routeConfigurator()->get('foo_route', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function a_route_with_trailing_slash_does_not_match_a_path_without_trailing_slash()
    {
        $this->routeConfigurator()->get('foo_route', '/foo/', RoutingTestController::class);
        
        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/foo/');
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function a_route_without_trailing_slash_does_not_match_a_path_with_trailing_slash()
    {
        $this->routeConfigurator()->get('foo_route', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('/foo/');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }
    
    /** @test */
    public function test_required_route_segments_and_trailing_slashes()
    {
        $this->routeConfigurator()->get(
            'route1',
            '/route1/{param1}/{param2}',
            [RoutingTestController::class, 'twoParams']
        );
        $this->routeConfigurator()->get(
            'route2',
            '/route2/{param1}/{param2}/',
            [RoutingTestController::class, 'twoParams']
        );
        
        $request = $this->frontendRequest('/route1/foo/bar/');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/route1/foo/bar');
        $this->assertResponseBody('foo:bar', $request);
        
        $request = $this->frontendRequest('/route2/foo/bar');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/route2/foo/bar/');
        $this->assertResponseBody('foo:bar', $request);
    }
    
    /** @test */
    public function test_optional_route_segments_and_trailing_slashes()
    {
        $this->routeConfigurator()->get(
            'route1',
            '/notrailing/{param1?}/{param2?}',
            [RoutingTestController::class, 'twoOptional']
        );
        $this->routeConfigurator()->get(
            'route2',
            '/trailing/{param1?}/{param2?}/',
            [RoutingTestController::class, 'twoOptional']
        );
        
        // Only with trailing
        $request = $this->frontendRequest('/trailing/foo');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/trailing/foo/');
        $this->assertResponseBody('foo:default2', $request);
        
        $request = $this->frontendRequest('/trailing/foo/bar');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/trailing/foo/bar/');
        $this->assertResponseBody('foo:bar', $request);
        
        $request = $this->frontendRequest('/notrailing/foo');
        $this->assertResponseBody('foo:default2', $request);
        
        $request = $this->frontendRequest('/notrailing/foo/');
        $this->assertResponseBody('', $request);
        
        $request = $this->frontendRequest('/notrailing/foo/bar');
        $this->assertResponseBody('foo:bar', $request);
        
        $request = $this->frontendRequest('/notrailing/foo/bar/');
        $this->assertResponseBody('', $request);
    }
    
}