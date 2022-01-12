<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Snicco\Support\Repository;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\Route\Route;
use Tests\Codeception\shared\UnitTest;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\Core\Routing\UrlMatcher\RoutingResult;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class RequestTest extends UnitTest
{
    
    private Request $request;
    use CreatePsrRequests;
    use CreatePsr17Factories;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->request = $this->frontendRequest('GET', '/foo');
    }
    
    public function testIsImmutable()
    {
        $request = TestRequest::from('GET', 'foo');
        
        $next = $request->withMethod('POST');
        
        $this->assertInstanceOf(Request::class, $request);
        $this->assertInstanceOf(Request::class, $next);
        
        $this->assertNotSame($request, $next);
        
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('POST', $next->getMethod());
    }
    
    public function testGetPath()
    {
        $request = TestRequest::from('GET', '/foo/bar');
        $this->assertSame('/foo/bar', $request->path());
        
        $request = TestRequest::from('GET', '/foo/bar/');
        $this->assertSame('/foo/bar/', $request->path());
        
        $request = TestRequest::from('GET', '/');
        $this->assertSame('/', $request->path());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('/foo/bar', $request->path());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('/foo/bar/', $request->path());
    }
    
    public function testGetFullPath()
    {
        $request = TestRequest::from('GET', '/foo/bar');
        $this->assertSame('/foo/bar', $request->fullRequestTarget());
        
        $request = TestRequest::from('GET', '/foo/bar/');
        $this->assertSame('/foo/bar/', $request->fullRequestTarget());
        
        $request = TestRequest::from('GET', '/');
        $this->assertSame('/', $request->fullRequestTarget());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('/foo/bar?baz=biz', $request->fullRequestTarget());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('/foo/bar/?baz=biz', $request->fullRequestTarget());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz#section');
        $this->assertSame('/foo/bar?baz=biz#section', $request->fullRequestTarget());
    }
    
    public function testGetUrl()
    {
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar');
        $this->assertSame('https://foo.com/foo/bar', $request->url());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/');
        $this->assertSame('https://foo.com/foo/bar/', $request->url());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('https://foo.com/foo/bar', $request->url());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('https://foo.com/foo/bar/', $request->url());
    }
    
    public function testGetFullUrl()
    {
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar');
        $this->assertSame('https://foo.com/foo/bar', $request->fullUrl());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/');
        $this->assertSame('https://foo.com/foo/bar/', $request->fullUrl());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('https://foo.com/foo/bar?baz=biz', $request->fullUrl());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('https://foo.com/foo/bar/?baz=biz', $request->fullUrl());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz#section');
        $this->assertSame('https://foo.com/foo/bar?baz=biz#section', $request->fullUrl());
    }
    
    public function testCookies()
    {
        $cookies = $this->request->cookies();
        $this->assertInstanceOf(Repository::class, $cookies);
        $this->assertSame([], $cookies->all());
        
        $request = $this->request->withCookies(['foo' => 'bar']);
        $cookies = $request->cookies();
        $this->assertInstanceOf(Repository::class, $cookies);
        $this->assertSame(['foo' => 'bar'], $cookies->all());
    }
    
    public function testGetLoadingScript()
    {
        $request = TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'index.php']);
        $this->assertSame('index.php', $request->loadingScript());
        
        $request =
            TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'wp-admin/edit.php']);
        $this->assertSame('wp-admin/edit.php', $request->loadingScript());
    }
    
    public function testRouteIs()
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foobar', ['GET']);
        
        $request = $this->request->withRoutingResult(RoutingResult::match($route));
        
        $this->assertFalse($request->routeIs('bar'));
        $this->assertTrue($request->routeIs('foobar'));
        $this->assertTrue($request->routeIs('bar', 'foobar'));
        $this->assertFalse($request->routeIs(['bar', 'baz']));
        
        $this->assertTrue($request->routeIs('foo*'));
    }
    
    public function testFullUrlIs()
    {
        $request = TestRequest::fromFullUrl('GET', 'https://example.com/foo/bar');
        
        $this->assertFalse($request->fullUrlIs('https://example.com/foo/'));
        $this->assertFalse($request->fullUrlIs('https://example.com/foo/bar/'));
        $this->assertTrue($request->fullUrlIs('https://example.com/foo/bar'));
        $this->assertTrue($request->fullUrlIs('https://example.com/foo/*'));
    }
    
    public function testPathIs()
    {
        $request = TestRequest::fromFullUrl('GET', 'https://example.com/foo/bar');
        
        $this->assertFalse($request->pathIs('/foo'));
        $this->assertFalse($request->pathIs('foo'));
        
        $this->assertTrue($request->pathIs('foo/bar'));
        $this->assertTrue($request->pathIs('/foo/bar'));
        
        $this->assertFalse($request->pathIs('/foo/bar/'));
        
        $this->assertTrue($request->pathIs('/foo/*'));
    }
    
    public function testDecodedPath()
    {
        $request = $this->frontendRequest('GET', '/münchen/düsseldorf');
        
        $this->assertSame('/münchen/düsseldorf', $request->decodedPath());
        
        $request = $this->frontendRequest('GET', '/AC%2FDC');
        
        $this->assertSame('/AC%2FDC', $request->decodedPath());
    }
    
    /** @test */
    public function test_IsSecure()
    {
        $request = $this->frontendRequest('GET', 'http://foobar.com');
        $this->assertFalse($request->isSecure());
        
        $request = $this->frontendRequest('GET', 'https://foobar.com');
        $this->assertTrue($request->isSecure());
    }
    
}