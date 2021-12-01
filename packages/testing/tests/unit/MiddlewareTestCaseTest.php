<?php

declare(strict_types=1);

namespace Tests\Testing\unit;

use Mockery;
use RuntimeException;
use Snicco\Http\Delegate;
use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Testing\TestResponse;
use Snicco\Testing\MiddlewareTestCase;
use Psr\Http\Message\ResponseInterface;
use Snicco\Contracts\RouteUrlGenerator;
use PHPUnit\Framework\ExpectationFailedException;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;
use Snicco\Testing\Assertable\MiddlewareTestResponse;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class MiddlewareTestCaseTest extends MiddlewareTestCase
{
    
    use CreatePsr17Factories;
    
    /** @test */
    public function testResponseIsTestResponse()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $this->response_factory->html('foo');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertInstanceOf(TestResponse::class, $response);
        
        $response->assertSee('foo');
    }
    
    /** @test */
    public function testAssertNextWasCalledCanPass()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function testAssertNextWasCalledCanFail()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $this->response_factory->html('foo');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        
        try {
            $response->assertNextMiddlewareCalled();
            $this->fail('Test assertion gave false positive outcome.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith("The next middleware was not called.", $e->getMessage());
        }
    }
    
    /** @test */
    public function testNextWasNotCalledCanPass()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $this->response_factory->html('foo');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function testNextWasNotCalledCanFail()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        
        try {
            $response->assertNextMiddlewareNotCalled();
            $this->fail('Test assertion gave false positive outcome.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith('The next middleware was called.', $e->getMessage());
        }
    }
    
    /** @test */
    public function testRequestChangesCanBeAsserted()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request->withAttribute('foo', 'bar'));
            }
            
        };
        
        $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $this->assertSame('bar', $this->receivedRequest()->getAttribute('foo'));
    }
    
    /** @test */
    public function testExceptionWhenRequestAssertionsAreMadeWithoutNextMiddleware()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $this->response_factory->make();
            }
            
        };
        
        $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        try {
            $this->assertSame('bar', $this->receivedRequest()->getAttribute('foo'));
            $this->fail('Test assertions gave false result.');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('The next middleware was not called.', $e->getMessage());
        }
    }
    
    /** @test */
    public function testOriginalResponseIsPreservedWithDelayedMiddleware()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                $response = $next($request);
                return $response->withHeader('foo', 'bar');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        
        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('foo', 'bar');
    }
    
    /** @test */
    public function testCustomResponseForNextMiddleware()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->html('foo');
        });
        
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertSee('foo');
    }
    
    /** @test */
    public function testNextMiddlewareCalledIsStillTrueWithCustomResponse()
    {
        $this->setNextMiddlewareResponse(function () {
            return $this->response_factory->html('foo');
        });
        
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function testNextMiddlewareCalledIsTrueForCustomResponseInTestMiddleware()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                $response1 = $next($request);
                
                return $this->response_factory->html('foo');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareCalled();
        $response->assertSee('foo');
    }
    
    /** @test */
    public function testCalledIsResetAfterEveryMiddlewareRun()
    {
        $middleware = new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                if ($request->isGet()) {
                    $response1 = $next($request);
                }
                return $this->response_factory->html('foo');
            }
            
        };
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('GET', '/foo'));
        
        $response->assertNextMiddlewareCalled();
        $response->assertSee('foo');
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest('POST', '/foo'));
        $response->assertNextMiddlewareNotCalled();
        $response->assertSee('foo');
    }
    
    protected function routeUrlGenerator() :RouteUrlGenerator
    {
        return Mockery::mock(FastRouteUrlGenerator::class);
    }
    
    protected function viewEngine() :ViewEngine
    {
        return new ViewEngine(new TestViewFactory());
    }
    
}

