<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Tests\Core\RoutingTestCase;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Middleware\MethodOverride;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Snicco\Core\EventDispatcher\Events\ResponseSent;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class HttpKernelTest extends RoutingTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->event_dispatcher->reset();
        $this->event_dispatcher->fake(ResponseSent::class);
    }
    
    /** @test */
    public function a_delegate_response_is_returned_by_default_if_no_route_matches()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        $test_response = $this->runKernel($this->frontendRequest('GET', '/bar'));
        
        $this->assertInstanceOf(DelegatedResponse::class, $test_response->psr_response);
    }
    
    /** @test */
    public function a_normal_response_will_be_returned_for_matching_routes()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $test_response = $this->runKernel($this->frontendRequest('GET', '/foo'));
        
        $this->assertNotInstanceOf(DelegatedResponse::class, $test_response->psr_response);
        $this->assertInstanceOf(Response::class, $test_response->psr_response);
        
        $this->assertSame(RoutingTestController::static, $test_response->body());
    }
    
    /** @test */
    public function methods_can_be_overwritten()
    {
        $this->routeConfigurator()->put('r1', '/foo', RoutingTestController::class);
        
        $test_response = $this->runKernel(
            $this->frontendRequest('POST', '/foo')->withHeader(MethodOverride::HEADER, 'PUT')
        );
        
        $this->assertSame(RoutingTestController::static, $test_response->body());
    }
    
    /** @test */
    public function the_response_is_prepared_and_fixed_for_common_mistakes()
    {
        // We only verify that the corresponding middleware gets called
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        $test_response = $this->runKernel($this->frontendRequest('GET', '/foo'));
        
        $test_response->assertHeader('content-length', strlen(RoutingTestController::static));
    }
    
}
