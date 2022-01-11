<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Snicco\Core\Routing\Route;
use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Middleware\Authenticate;
use Snicco\Core\Routing\Internal\UrlGenerationContext;

class AuthenticateTest extends MiddlewareTestCase
{
    
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
        $this->setUrlGenerationContext(UrlGenerationContext::fromRequest($request));
        
        $route = Route::create('/login', Route::DELEGATE, 'login');
        $this->routes()->add($route);
        
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
        $request = $this->frontendRequest('GET', '/foo')->withAddedHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        )->withAddedHeader('Accept', 'application/json');
        
        $response = $this->runMiddleware(new Authenticate(fn() => 0), $request);
        
        $response->assertStatus(401)->assertIsJson();
        $response->assertNextMiddlewareNotCalled();
    }
    
}
