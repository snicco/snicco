<?php

declare(strict_types=1);

namespace Tests\unit\Auth;

use Mockery;
use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Http\Delegate;
use Snicco\Session\Session;
use Tests\MiddlewareTestCase;
use Snicco\Auth\Middleware\AuthUnconfirmed;
use Snicco\Session\Drivers\ArraySessionDriver;

class AuthUnconfirmedTest extends MiddlewareTestCase
{
    
    public function newMiddleware() :AuthUnconfirmed
    {
        
        return new AuthUnconfirmed($this->generator);
    }
    
    /** @test */
    public function unconfirmed_session_can_bypass_the_middleware()
    {
        
        $session = new Session(new ArraySessionDriver(10));
        $request = $this->request->withSession($session);
        
        $response = $this->runMiddleware($request);
        
        $this->assertStatusCode(200, $response);
        
    }
    
    /** @test */
    public function confirmed_sessions_are_redirected_back()
    {
        
        $session = new Session(new ArraySessionDriver(10));
        $session->confirmAuthUntil(300);
        $request = $this->request->withSession($session)
                                 ->withHeader('referer', 'https://foobar.com/foo/bar');
        
        $this->generator->setRequestResolver(fn() => $request);
        
        $response = $this->runMiddleware($request);
        
        $this->assertStatusCode(302, $response);
        $this->assertHeader('Location', 'https://foobar.com/foo/bar', $response);
        
    }
    
    /** @test */
    public function without_referer_header_sessions_are_redirected_to_the_dashboard()
    {
        
        $session = new Session(new ArraySessionDriver(10));
        $session->confirmAuthUntil(300);
        $request = $this->request->withSession($session);
        
        $this->generator->setRequestResolver(fn() => $request);
        
        $response = $this->runMiddleware($request);
        
        $this->assertStatusCode(302, $response);
        $this->assertHeader('Location', '/dashboard', $response);
        
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        WP::shouldReceive('wpAdminFolder')->andReturn('wp-admin');
        
        $this->route_action = new Delegate(fn() => $this->response_factory->make());
        $route = new Route(['GET'], '/dashboard', function () {
        });
        $route->name('dashboard');
        $this->routes->add($route);
        
    }
    
    protected function tearDown() :void
    {
        
        Mockery::close();
        WP::reset();
        parent::tearDown();
        
    }
    
}