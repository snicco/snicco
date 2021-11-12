<?php

declare(strict_types=1);

namespace Tests\unit\Auth\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Session\Session;
use Tests\MiddlewareTestCase;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Middleware\ConfirmAuth;
use Snicco\Testing\Concerns\TravelsTime;
use Tests\concerns\CreateDefaultWpApiMocks;
use Snicco\Session\Drivers\ArraySessionDriver;

class ConfirmAuthTest extends MiddlewareTestCase
{
    
    use CreateDefaultWpApiMocks;
    use TravelsTime;
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createDefaultWpApiMocks();
        $this->backToPresent();
        $route = new Route(['GET'], '/auth/confirm', function () { });
        $route->name('auth.confirm');
        $this->routes->add($route);
        $this->routes->addToUrlMatcher();
        $this->request = $this->frontendRequest();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
        WP::reset();
        $this->backToPresent();
    }
    
    /** @test */
    public function a_missing_auth_confirmed_token_does_not_grant_access_to_a_route()
    {
        $request = $this->request->withSession($this->newSession());
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request);
        
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function unconfirmed_users_are_redirect_to_the_correct_route()
    {
        $request = $this->request->withSession($this->newSession());
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request);
        
        $response->assertNextMiddlewareNotCalled()->assertRedirect('/auth/confirm');
    }
    
    /** @test */
    public function an_expired_auth_token_does_not_grant_access()
    {
        $request = $this->request->withSession($session = $this->newSession());
        $session->confirmAuthUntil(200);
        
        $this->travelIntoFuture(201);
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request);
        
        $response->assertNextMiddlewareNotCalled()->assertRedirect('/auth/confirm');
    }
    
    /** @test */
    public function a_valid_token_grants_access()
    {
        $request = $this->request->withSession($session = $this->newSession());
        $session->confirmAuthUntil(200);
        
        $this->travelIntoFuture(199);
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request);
        
        $response->assertNextMiddlewareCalled()->assertOk();
    }
    
    /** @test */
    public function the_current_url_is_saved_as_intended_url_to_the_session_on_get_requests()
    {
        $request = $this->request->withSession($session = $this->newSession());
        $session->confirmAuthUntil(200);
        
        $this->travelIntoFuture(201);
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request);
        
        $response->assertNextMiddlewareNotCalled();
        $this->assertSame($request->fullPath(), $session->getIntendedUrl());
    }
    
    /** @test */
    public function the_previous_url_is_saved_for_post_request_as_the_intended_url()
    {
        $request = $this->request->withSession($session = $this->newSession());
        $session->setPreviousUrl('/foo/bar');
        
        $response = $this->runMiddleware(new ConfirmAuth(), $request->withMethod('POST'));
        
        $this->assertSame('/foo/bar', $session->getIntendedUrl());
    }
    
    private function newSession() :Session
    {
        return new Session(new ArraySessionDriver(10));
    }
    
}