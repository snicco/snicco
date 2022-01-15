<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Middleware\Authenticate;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

class AuthenticateTest extends InternalMiddlewareTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->withRoutes([Route::create('/login', Route::DELEGATE, 'login')]);
    }
    
    /** @test */
    public function logged_in_users_can_access_the_route()
    {
        $middleware = new Authenticate(fn() => 1);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function logged_out_users_cant_access_the_route()
    {
        $request = $this->frontendRequest('GET', 'https://mysite.com/foo');
        
        $middleware = new Authenticate(fn() => 0);
        
        $response = $this->runMiddleware(
            $middleware,
            $request
        );
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect('/login?intended=https://mysite.com/foo');
    }
    
    /** @test */
    public function json_responses_are_returned_for_ajax_requests()
    {
        $request = $this->frontendRequest('GET', '/foo')
                        ->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
                        ->withAddedHeader('Accept', 'application/json');
        
        $response = $this->runMiddleware(new Authenticate(fn() => 0), $request);
        
        $response->assertStatus(401)->assertIsJson();
        $response->assertNextMiddlewareNotCalled();
    }
    
}
