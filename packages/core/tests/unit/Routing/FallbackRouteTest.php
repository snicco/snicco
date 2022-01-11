<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use LogicException;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class FallbackRouteTest extends RoutingTestCase
{
    
    /** @test */
    public function users_can_create_a_custom_fallback_web_route()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);
        
        $request = $this->frontendRequest('GET', '/bar');
        $this->assertResponseBody('fallback:bar', $request);
        
        $request = $this->frontendRequest('GET', '/bar/baz');
        $this->assertResponseBody('fallback:bar/baz', $request);
    }
    
    /** @test */
    public function throws_an_exception_if_a_route_is_created_after_the_fallback_route()
    {
        $this->expectExceptionMessage(LogicException::class);
        $this->expectExceptionMessage(
            "Route [route1] was registered after a fallback route was defined."
        );
        
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);
        $this->routeConfigurator()->get('route1', '/foo');
    }
    
    /** @test */
    public function the_fallback_route_does_not_match_admin_requests()
    {
        $this->routeConfigurator()->fallback(RoutingTestController::class);
        
        $response = $this->runKernel($this->adminRequest('GET', 'foo'));
        $response->assertDelegated();
    }
    
    /** @test */
    public function the_fallback_route_will_not_match_for_requests_that_are_specified_in_the_exclusion_list()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);
        
        $this->assertResponseBody(
            'fallback:foo.bar',
            $this->frontendRequest('GET', '/foo.bar')
        );
        
        // These are excluded by default
        $this->assertEmptyBody($this->frontendRequest('GET', '/favicon.ico'));
        $this->assertEmptyBody($this->frontendRequest('GET', '/robots.txt'));
        $this->assertEmptyBody($this->frontendRequest('GET', '/sitemap.xml'));
    }
    
    /** @test */
    public function custom_exclusions_words_can_be_specified()
    {
        $this->routeConfigurator()
             ->fallback([RoutingTestController::class, 'fallback'], ['foo', 'bar']);
        
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/foobar')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/foo')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/bar')
        );
        
        $this->assertResponseBody('fallback:baz', $this->frontendRequest('GET', '/baz'));
        $this->assertResponseBody(
            'fallback:robots.txt',
            $this->frontendRequest('GET', '/robots.txt')
        );
    }
    
    /** @test */
    public function an_exception_is_thrown_for_non_string_exclusions()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('All fallback excludes have to be strings.');
        $this->routeConfigurator()
             ->fallback([RoutingTestController::class, 'fallback'], ['foo', 1]);
    }
    
    /** @test */
    public function the_pipe_symbol_can_be_passed()
    {
        $this->routeConfigurator()
             ->fallback([RoutingTestController::class, 'fallback'], ['foo|bar', 'baz']);
        
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/foo')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/bar')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('GET', '/baz')
        );
        
        $this->assertResponseBody('fallback:biz', $this->frontendRequest('GET', '/biz'));
    }
    
}