<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use LogicException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Tests\RoutingTestCase;
use Snicco\Component\HttpRouting\Middleware\MethodOverride;
use Snicco\Component\HttpRouting\Http\Responses\DelegatedResponse;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

class HttpKernelTest extends RoutingTestCase
{
    
    /** @test */
    public function the_kernel_throws_an_exception_if_a_request_has_no_type_specified()
    {
        $psr = $this->psrServerRequestFactory()->createServerRequest('GET', '/foo');
        $request = new Request($psr);
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "The HttpKernel tried to handle a request without a declared type. This is not allowed."
        );
        
        $this->runKernel($request);
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
