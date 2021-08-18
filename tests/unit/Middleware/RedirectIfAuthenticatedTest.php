<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Http\Delegate;
use Snicco\Routing\Route;
use Tests\stubs\TestRequest;
use Snicco\Http\ResponseFactory;
use Tests\helpers\AssertsResponse;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Tests\helpers\CreateDefaultWpApiMocks;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedTest extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use CreateDefaultWpApiMocks;
    
    private RedirectIfAuthenticated $middleware;
    private Delegate                $route_action;
    private TestRequest             $request;
    private ResponseFactory         $response;
    
    /** @test */
    public function guests_can_access_the_route()
    {
        
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $response = $this->newMiddleware()->handle($this->request, $this->route_action);
        
        $this->assertOutput('FOO', $response);
        
    }
    
    private function newMiddleware(string $redirect_url = null) :RedirectIfAuthenticated
    {
        
        $m = new RedirectIfAuthenticated($this->generator, $redirect_url);
        $m->setResponseFactory($this->response);
        
        return $m;
        
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
        
        $response = $this->newMiddleware()->handle($this->request, $this->route_action);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStatusCode(302, $response);
        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
        
    }
    
    /** @test */
    public function logged_in_users_can_be_redirected_to_custom_urls()
    {
        
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        WP::shouldReceive('homeUrl')
          ->with('', 'https')
          ->andReturn(SITE_URL);
        
        $response = $this->newMiddleware('/custom-home-page')
                         ->handle($this->request, $this->route_action);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/custom-home-page', $response->getHeaderLine('Location'));
    }
    
    protected function beforeTestRun()
    {
        
        $response = $this->createResponseFactory();
        $this->route_action = new Delegate(fn() => $response->html('FOO'));
        $this->response = $response;
        $this->request = TestRequest::from('GET', '/foo');
        WP::shouldReceive('homeUrl')->andReturn('https://foobar.com')->byDefault();
        
    }
    
    protected function beforeTearDown()
    {
        
        WP::reset();
        Mockery::close();
        
    }
    
}
