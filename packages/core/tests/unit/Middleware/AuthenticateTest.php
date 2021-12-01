<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Tests\Core\MiddlewareTestCase;
use Snicco\Middleware\Authenticate;

class AuthenticateTest extends MiddlewareTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function logged_in_users_can_access_the_route()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        $middleware = new Authenticate();
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function logged_out_users_cant_access_the_route()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $middleware = new Authenticate();
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect();
    }
    
    /** @test */
    public function by_default_users_get_redirected_to_wp_login_with_the_current_url_added_to_the_query_args()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        WP::shouldReceive('loginUrl')
          ->andReturnUsing(fn($redirect_to) => 'https://foo.com/login?redirect_to='.$redirect_to);
        
        $enc = urlencode('üäö');
        $request = $this->frontendRequest('GET', 'https://foo.com/'.$enc.'?param=1');
        
        $response = $this->runMiddleware(new Authenticate(), $request);
        
        $expected = '/login?redirect_to=/'.$enc.'?param=1';
        
        $response->assertLocation($expected);
    }
    
    /** @test */
    public function users_can_be_redirected_to_a_custom_url()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        WP::shouldReceive('loginUrl')
          ->andReturnUsing(fn($redirect_to) => 'https://foo.com/login?redirect_to='.$redirect_to);
        
        $response = $this->runMiddleware(
            new Authenticate('/my-custom-login'),
            $this->frontendRequest('GET', '/foo')
        );
        
        $response->assertLocation('/login?redirect_to=/my-custom-login');
    }
    
    /** @test */
    public function json_responses_are_returned_for_ajax_requests()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        $request = $this->frontendRequest('GET', '/foo')->withAddedHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        )
                        ->withAddedHeader('Accept', 'application/json');
        
        $response = $this->runMiddleware(new Authenticate(), $request);
        
        $response->assertStatus(401)->assertIsJson();
    }
    
}
