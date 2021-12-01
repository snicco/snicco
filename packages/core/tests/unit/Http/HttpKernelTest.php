<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
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
    
    /** @test */
    public function the_request_is_rebound_in_the_container_before_any_application_middleware_is_run()
    {
        $this->createRoutes(function () {
            //
            
        });
        
        $request = $this->adminAjaxRequest('GET', 'test_form');
        
        $this->assertSame('/wp-admin/admin-ajax.php', $request->routingPath());
        
        $this->container->instance(Request::class, $request);
        
        $this->assertResponse('', $request);
        
        /** @var Request $request */
        $request = $this->container->make(Request::class);
        
        $this->assertSame('/wp-admin/admin-ajax.php/test_form', $request->routingPath());
    }
    
    /** @test */
    public function the_request_is_rebound_in_the_container_before_the_route_is_run()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return 'foo';
            })->middleware('foobar');
        });
        
        $this->withMiddlewareGroups([
            'foobar' => [
                ChangeRequestMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foo', $request);
        
        /** @var Request $request */
        $request = $this->container->make(Request::class);
        $this->assertSame('bar', $request->getAttribute('foo'));
    }
    
}

class ChangeRequestMiddleware extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        return $next($request->withAttribute('foo', 'bar'));
    }
    
}