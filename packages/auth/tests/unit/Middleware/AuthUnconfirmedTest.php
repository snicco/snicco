<?php

declare(strict_types=1);

namespace Tests\Auth\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Routing\UrlGenerator;
use Tests\Core\MiddlewareTestCase;
use Snicco\Auth\Middleware\AuthUnconfirmed;
use Snicco\Session\Drivers\ArraySessionDriver;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class AuthUnconfirmedTest extends MiddlewareTestCase
{
    
    use CreateDefaultWpApiMocks;
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        WP::shouldReceive('wpAdminFolder')->andReturn('wp-admin');
        
        $route = new Route(['GET'], '/dashboard', function () {
        });
        $route->name('dashboard');
        $this->routes->add($route);
        $this->routes->addToUrlMatcher();
        $this->request = $this->frontendRequest();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function unconfirmed_session_can_bypass_the_middleware()
    {
        $session = new Session(new ArraySessionDriver(10));
        
        $response =
            $this->runMiddleware($this->newMiddleware(), $this->request->withSession($session));
        
        $response->assertOk()->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function confirmed_sessions_are_redirected_back()
    {
        $session = new Session(new ArraySessionDriver(10));
        $session->confirmAuthUntil(300);
        $request = $this->request->withSession($session)
                                 ->withHeader('referer', 'https://foobar.com/foo/bar');
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareNotCalled();
        
        $response->assertRedirect('https://foobar.com/foo/bar', 302);
    }
    
    /** @test */
    public function without_referer_header_sessions_are_redirected_to_the_dashboard()
    {
        $session = new Session(new ArraySessionDriver(10));
        $session->confirmAuthUntil(300);
        $request = $this->request->withSession($session);
        
        $response = $this->runMiddleware($this->newMiddleware(), $request);
        
        $response->assertNextMiddlewareNotCalled();
        
        $response->assertRedirect('/dashboard', 302);
    }
    
    protected function urlGenerator() :UrlGenerator
    {
        $url = parent::urlGenerator();
        $this->url = $url;
        return $url;
    }
    
    private function newMiddleware() :AuthUnconfirmed
    {
        return new AuthUnconfirmed($this->url);
    }
    
}