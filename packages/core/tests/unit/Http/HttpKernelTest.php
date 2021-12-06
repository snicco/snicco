<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Tests\Core\RoutingTestCase;
use Snicco\Contracts\ResponseFactory;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\EventDispatcher\Events\ResponseSent;
use Tests\Core\fixtures\TestDoubles\HeaderStack;
use Snicco\Middleware\Core\EvaluateResponseMiddleware;
use Snicco\ExceptionHandling\Exceptions\NotFoundException;

class HttpKernelTest extends RoutingTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->event_dispatcher->reset();
        $this->event_dispatcher->fake(ResponseSent::class);
    }
    
    /** @test */
    public function no_response_gets_send_when_no_route_matched()
    {
        $this->createRoutes(function () {
            $this->router->get('foo')->handle(fn() => 'foo');
        });
        
        $request = $this->frontendRequest('GET', '/bar');
        
        $this->assertEmptyResponse($request);
        HeaderStack::assertNoStatusCodeSent();
    }
    
    /** @test */
    public function for_matching_request_headers_and_body_get_send()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', fn() => 'foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foo', $request);
        HeaderStack::assertHasStatusCode(200);
    }
    
    /** @test */
    public function an_event_gets_dispatched_when_a_response_got_send()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', fn() => 'foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foo', $request);
        $this->event_dispatcher->assertDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function an_invalid_response_returned_from_the_handler_will_lead_to_an_exception()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', fn() => 1);
        });
        
        $this->expectExceptionMessage('Invalid response returned by a route.');
        
        $this->runKernel($this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function an_exception_is_thrown_when_the_kernel_must_match_web_routes_and_no_route_matched()
    {
        $this->createRoutes(function () {
            $this->router->get('/bar', fn() => 'bar');
        });
        
        $this->container->singleton(EvaluateResponseMiddleware::class, function () {
            return new EvaluateResponseMiddleware(true);
        });
        
        $this->expectException(NotFoundException::class);
        
        $this->runKernel($this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function a_redirect_response_will_shut_down_the_script_by_dispatching_an_event()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (ResponseFactory $factory) {
                return $factory->redirect()->to('bar');
            });
        });
        
        $this->runKernel($this->frontendRequest('GET', '/foo'));
        
        $this->event_dispatcher->assertDispatched(ResponseSent::class, function ($event) {
            return $event->response instanceof RedirectResponse;
        });
    }
    
}
