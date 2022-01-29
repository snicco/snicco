<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPCap\Tests;

use Mockery;
use Snicco\Middleware\WPCap\Authorize;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

class AuthorizeTest extends MiddlewareTestCase
{
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->request = $this->frontendRequest('/foo');
        Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function a_user_with_given_capabilities_can_access_the_route()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('currentUserCan')
           ->once()
           ->with('manage_options')
           ->andReturnTrue();
        
        $m = $this->newMiddleware($wp, 'manage_options');
        
        $response = $this->runMiddleware($m, $this->request);
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function a_user_without_authorisation_to_the_route_will_throw_an_exception()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('currentUserCan')
           ->once()
           ->with('manage_options')
           ->andReturnFalse();
        
        $m = $this->newMiddleware($wp, 'manage_options');
        
        try {
            $response = $this->runMiddleware($m, $this->request);
            $this->fail("An Exception should have been thrown.");
        } catch (HttpException $e) {
            $this->assertSame(403, $e->statusCode());
            $this->assertSame(
                "Authorization failed for path [/foo] with required capability [manage_options].",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function the_user_can_be_authorized_against_a_resource()
    {
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('currentUserCan')
           ->once()
           ->with('manage_options', 1)
           ->andReturnTrue();
        
        $m = $this->newMiddleware($wp, 'manage_options', 1);
        
        $response = $this->runMiddleware($m, $this->request);
        $response->assertNextMiddlewareCalled();
        
        $wp = Mockery::mock(ScopableWP::class);
        $wp->shouldReceive('currentUserCan')
           ->once()
           ->with('manage_options', 10)
           ->andReturnFalse();
        
        $m = $this->newMiddleware($wp, 'manage_options', 10);
        
        try {
            $response = $this->runMiddleware($m, $this->request);
            $this->fail('An Exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->statusCode());
            $this->assertSame(
                'Authorization failed for path [/foo] with required capability [manage_options].',
                $e->getMessage()
            );
        }
    }
    
    private function newMiddleware(ScopableWP $wp, string $cap, $id = null) :Authorize
    {
        return new Authorize($wp, $cap, $id);
    }
    
}


