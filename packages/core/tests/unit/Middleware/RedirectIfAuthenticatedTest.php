<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Core\Support\WP;
use Snicco\Core\Routing\Route;
use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Middleware\RedirectIfAuthenticated;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class RedirectIfAuthenticatedTest extends MiddlewareTestCase
{
    
    use CreateDefaultWpApiMocks;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createDefaultWpApiMocks();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function guests_can_access_the_route()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $response = $this->runMiddleware($this->newMiddleware(), $this->frontendRequest());
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function logged_in_users_are_redirected_to_the_home_url()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        WP::shouldReceive('adminUrl')
          ->andReturn('/wp-admin');
        
        $route = new Route(['GET'], '/dashboard', function () { });
        $route->name('dashboard');
        $this->routes->add($route);
        $this->routes->addToUrlMatcher();
        
        $response = $this->runMiddleware($this->newMiddleware(), $this->frontendRequest());
        
        $response->assertRedirect('/dashboard');
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function logged_in_users_can_be_redirected_to_custom_urls()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        
        $response = $this->runMiddleware(
            $this->newMiddleware('/custom-home-page'),
            $this->frontendRequest()
        );
        
        $response->assertRedirect('/custom-home-page');
        $response->assertNextMiddlewareNotCalled();
    }
    
    private function newMiddleware(string $redirect_url = null) :RedirectIfAuthenticated
    {
        return new RedirectIfAuthenticated($redirect_url);
    }
    
}
