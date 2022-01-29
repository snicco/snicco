<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Middleware\Authenticate;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

class AuthenticateTest extends InternalMiddlewareTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->withRoutes([Route::create('/login', Route::DELEGATE, 'login')]);
    }
    
    /** @test */
    public function logged_in_users_can_access_the_route()
    {
        $middleware = new Authenticate(fn() => 1);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function logged_out_users_cant_access_the_route()
    {
        $request = $this->frontendRequest('https://mysite.com/foo');
        
        $middleware = new Authenticate(fn() => 0);
        
        try {
            $response = $this->runMiddleware(
                $middleware,
                $request
            );
            $this->fail("An exception should have been thrown");
        } catch (HttpException $e) {
            $this->assertSame(401, $e->statusCode());
            $this->assertSame("Missing authentication for request path [/foo].", $e->getMessage());
        }
    }
    
}
