<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Tests\UnitTest;
use Snicco\Http\Delegate;
use Tests\stubs\TestRequest;
use Snicco\Http\ResponseFactory;
use Tests\helpers\AssertsResponse;
use Snicco\Middleware\TrailingSlash;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Snicco\Http\Responses\RedirectResponse;

class TrailingSlashTest extends UnitTest
{
    
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use AssertsResponse;
    
    private ResponseFactory $response_factory;
    private Delegate        $delegate;
    
    public function testRedirectNoSlashToTrailingSlash()
    {
        
        $request =
            TestRequest::fromFullUrl('GET', 'https://foo.com/bar')->withLoadingScript('index.php');
        
        $response = $this->newMiddleware(true)->handle($request, $this->delegate);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertHeader('Location', '/bar/', $response);
        $this->assertStatusCode(301, $response);
        
    }
    
    private function newMiddleware($trailing_slash) :TrailingSlash
    {
        
        $this->response_factory = $this->createResponseFactory();
        
        $this->delegate = new Delegate(fn() => $this->response_factory->make(200));
        
        $m = new TrailingSlash($trailing_slash);
        $m->setResponseFactory($this->response_factory);
        return $m;
        
    }
    
    /** @test */
    public function testRedirectSlashToNoSlash()
    {
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar/');
        $request = $request->withLoadingScript('index.php');
        
        $response = $this->newMiddleware(false)->handle($request, $this->delegate);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertHeader('Location', '/bar', $response);
        $this->assertStatusCode(301, $response);
        
    }
    
    public function testNoRedirectIfMatches()
    {
        
        $request =
            TestRequest::fromFullUrl('GET', 'https://foo.com/bar')->withLoadingScript('index.php');
        $response = $this->newMiddleware(false)->handle($request, $this->delegate);
        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertStatusCode(200, $response);
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar/');
        $response = $this->newMiddleware(true)->handle($request, $this->delegate);
        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertStatusCode(200, $response);
        
    }
    
}
