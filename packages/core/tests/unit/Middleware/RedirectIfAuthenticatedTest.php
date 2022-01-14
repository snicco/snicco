<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Closure;
use Snicco\Core\Routing\Route\Route;
use Tests\Core\InternalMiddlewareTestCase;
use Snicco\Core\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedTest extends InternalMiddlewareTestCase
{
    
    /** @test */
    public function guests_can_access_the_route()
    {
        $provider = $this->providerThatReturnsId(0);
        $response = $this->runMiddleware($this->newMiddleware($provider), $this->frontendRequest());
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function logged_in_users_are_redirected_to_a_dashboard_route_if_it_exists()
    {
        $route = Route::create('/dashboard', Route::DELEGATE, 'dashboard');
        $this->withRoutes([$route]);
        
        $provider = $this->providerThatReturnsId(1);
        
        $response = $this->runMiddleware($this->newMiddleware($provider), $this->frontendRequest());
        
        $response->assertRedirect('/dashboard');
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function logged_in_users_are_redirected_to_a_home_route_if_it_exists_and_no_dashboard_route_exists()
    {
        $route = Route::create('/home', Route::DELEGATE, 'home');
        $this->withRoutes([$route]);
        
        $provider = $this->providerThatReturnsId(1);
        
        $response = $this->runMiddleware($this->newMiddleware($provider), $this->frontendRequest());
        
        $response->assertRedirect('/home');
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function if_no_route_exists_users_are_redirected_to_the_root_domain_path()
    {
        $provider = $this->providerThatReturnsId(1);
        
        $response = $this->runMiddleware($this->newMiddleware($provider), $this->frontendRequest());
        
        $response->assertRedirect('/');
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function logged_in_users_can_be_redirected_to_custom_urls()
    {
        $provider = $this->providerThatReturnsId(1);
        
        $response = $this->runMiddleware(
            $this->newMiddleware($provider, '/custom-home-page'),
            $this->frontendRequest()
        );
        
        $response->assertRedirect('/custom-home-page');
        $response->assertNextMiddlewareNotCalled();
    }
    
    private function newMiddleware(Closure $provider, string $redirect_url = null) :RedirectIfAuthenticated
    {
        return new RedirectIfAuthenticated($provider, $redirect_url);
    }
    
    private function providerThatReturnsId(int $id) :Closure
    {
        return (fn() => $id);
    }
    
}
