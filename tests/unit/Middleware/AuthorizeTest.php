<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Http\Delegate;
use Tests\stubs\TestRequest;
use Snicco\Http\ResponseFactory;
use Snicco\Middleware\Authorize;
use Tests\helpers\AssertsResponse;
use Snicco\Middleware\Authenticate;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Snicco\ExceptionHandling\Exceptions\AuthorizationException;

class AuthorizeTest extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    private Authenticate    $middleware;
    private Delegate        $route_action;
    private TestRequest     $request;
    private ResponseFactory $response;
    
    /** @test */
    public function a_user_with_given_capabilities_can_access_the_route()
    {
        
        WP::shouldReceive('currentUserCan')
          ->with('manage_options')
          ->andReturnTrue();
        
        $response = $this->newMiddleware()->handle(
            $this->request,
            $this->route_action
        );
        
        $this->assertOutput('FOO', $response);
        
    }
    
    private function newMiddleware(string $capability = 'manage_options', ...$args)
    {
        
        return new Authorize($capability, ...$args);
        
    }
    
    /** @test */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception()
    {
        
        WP::shouldReceive('currentUserCan')
          ->with('manage_options')
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        
        $this->newMiddleware()->handle($this->request, $this->route_action);
        
    }
    
    /** @test */
    public function the_user_can_be_authorized_against_a_resource()
    {
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post', '10')
          ->once()
          ->andReturnTrue();
        
        $response = $this->newMiddleware('edit_post', '10')
                         ->handle($this->request, $this->route_action,);
        
        $this->assertOutput('FOO', $response);
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post', '10')
          ->once()
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        
        $this->newMiddleware('edit_post', '10')->handle(
            $this->request,
            $this->route_action,
        );
        
    }
    
    /** @test */
    public function several_wordpress_specific_arguments_can_be_passed()
    {
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post_meta', '10', 'test_key')
          ->once()
          ->andReturnTrue();
        
        $response = $this->newMiddleware('edit_post_meta', '10', 'test_key')
                         ->handle($this->request, $this->route_action,);
        
        $this->assertOutput('FOO', $response);
        
        WP::shouldReceive('currentUserCan')
          ->with('edit_post_meta', '10', 'test_key')
          ->once()
          ->andReturnFalse();
        
        $this->expectException(AuthorizationException::class);
        
        $this->newMiddleware('edit_post_meta', '10', 'test_key')
             ->handle($this->request, $this->route_action,);
        
    }
    
    protected function beforeTestRun()
    {
        
        $response = $this->createResponseFactory();
        $this->route_action = new Delegate(fn() => $response->html('FOO'));
        $this->request = TestRequest::from('GET', '/foo');
        WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();
        
    }
    
    protected function beforeTearDown()
    {
        
        WP::clearResolvedInstances();
        Mockery::close();
        
    }
    
}
