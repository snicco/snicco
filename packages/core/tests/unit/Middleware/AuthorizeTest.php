<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Mockery;
use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Middleware\Authorize;
use Tests\Core\MiddlewareTestCase;
use Snicco\ExceptionHandling\Exceptions\AuthorizationException;

class AuthorizeTest extends MiddlewareTestCase
{
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();
        $this->request = $this->frontendRequest('GET', 'foo');
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        WP::reset();
        Mockery::close();
    }
    
    /** @test */
    public function a_user_with_given_capabilities_can_access_the_route()
    {
        WP::shouldReceive('currentUserCan')
          ->with('manage_options')
          ->andReturnTrue();
        
        $response = $this->runMiddleware($this->newMiddleware(), $this->request);
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception()
    {
        WP::shouldReceive('currentUserCan')
          ->with('manage_options')
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        $response = $this->runMiddleware($this->newMiddleware(), $this->request);
    }
    
    /** @test */
    public function the_user_can_be_authorized_against_a_resource()
    {
        WP::shouldReceive('currentUserCan')
          ->with('edit_post', '10')
          ->once()
          ->andReturnTrue();
        
        $response = $this->runMiddleware(
            $this->newMiddleware('edit_post', '10'),
            $this->request,
        );
        
        $response->assertNextMiddlewareCalled();
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post', '10')
          ->once()
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        $this->runMiddleware(
            $this->newMiddleware('edit_post', '10'),
            $this->request,
        );
    }
    
    /** @test */
    public function several_wordpress_specific_arguments_can_be_passed()
    {
        WP::shouldReceive('currentUserCan')
          ->with('edit_post_meta', '10', 'test_key')
          ->once()
          ->andReturnTrue();
        
        $response = $this->runMiddleware(
            $this->newMiddleware('edit_post_meta', '10', 'test_key'),
            $this->request,
        );
        
        $response->assertNextMiddlewareCalled();
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post_meta', '10', 'test_key')
          ->once()
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        
        $response = $this->runMiddleware(
            $this->newMiddleware('edit_post_meta', '10', 'test_key'),
            $this->request,
        );
    }
    
    private function newMiddleware(string $capability = 'manage_options', ...$args) :Authorize
    {
        return new Authorize($capability, ...$args);
    }
    
}
