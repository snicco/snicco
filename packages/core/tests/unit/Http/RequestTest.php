<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Snicco\Support\Repository;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\Route\Route;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Routing\UrlMatcher\RoutingResult;
use Snicco\Core\Http\Exceptions\RequestHasNoType;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class RequestTest extends UnitTest
{
    
    private Request                $request;
    private ServerRequestInterface $psr_request;
    use CreatePsrRequests;
    use CreatePsr17Factories;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->request = $this->frontendRequest('GET', '/foo');
        $this->psr_request = $this->psrServerRequestFactory()->createServerRequest('GET', '/foo');
    }
    
    public function testIsImmutable()
    {
        $request = $this->frontendRequest('GET', 'foo');
        
        $next = $request->withMethod('POST');
        
        $this->assertInstanceOf(Request::class, $request);
        $this->assertInstanceOf(Request::class, $next);
        
        $this->assertNotSame($request, $next);
        
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('POST', $next->getMethod());
    }
    
    public function testGetPath()
    {
        $request = $this->frontendRequest('GET', '/foo/bar');
        $this->assertSame('/foo/bar', $request->path());
        
        $request = $this->frontendRequest('GET', '/foo/bar/');
        $this->assertSame('/foo/bar/', $request->path());
        
        $request = $this->frontendRequest('GET', '/');
        $this->assertSame('/', $request->path());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('/foo/bar', $request->path());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('/foo/bar/', $request->path());
    }
    
    public function testGetFullPath()
    {
        $request = $this->frontendRequest('GET', '/foo/bar');
        $this->assertSame('/foo/bar', $request->fullRequestTarget());
        
        $request = $this->frontendRequest('GET', '/foo/bar/');
        $this->assertSame('/foo/bar/', $request->fullRequestTarget());
        
        $request = $this->frontendRequest('GET', '/');
        $this->assertSame('/', $request->fullRequestTarget());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('/foo/bar?baz=biz', $request->fullRequestTarget());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('/foo/bar/?baz=biz', $request->fullRequestTarget());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz#section');
        $this->assertSame('/foo/bar?baz=biz#section', $request->fullRequestTarget());
    }
    
    public function testGetUrl()
    {
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar');
        $this->assertSame('https://foo.com/foo/bar', $request->url());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/');
        $this->assertSame('https://foo.com/foo/bar/', $request->url());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('https://foo.com/foo/bar', $request->url());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('https://foo.com/foo/bar/', $request->url());
    }
    
    public function testGetFullUrl()
    {
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar');
        $this->assertSame('https://foo.com/foo/bar', $request->fullUrl());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/');
        $this->assertSame('https://foo.com/foo/bar/', $request->fullUrl());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('https://foo.com/foo/bar?baz=biz', $request->fullUrl());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('https://foo.com/foo/bar/?baz=biz', $request->fullUrl());
        
        $request = $this->frontendRequest('GET', 'https://foo.com/foo/bar?baz=biz#section');
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
        $request = $this->frontendRequest('GET', 'https://example.com/foo/bar');
        
        $this->assertFalse($request->fullUrlIs('https://example.com/foo/'));
        $this->assertFalse($request->fullUrlIs('https://example.com/foo/bar/'));
        $this->assertTrue($request->fullUrlIs('https://example.com/foo/bar'));
        $this->assertTrue($request->fullUrlIs('https://example.com/foo/*'));
    }
    
    public function testPathIs()
    {
        $request = $this->frontendRequest('GET', 'https://example.com/foo/bar');
        
        $this->assertFalse($request->pathIs('/foo'));
        $this->assertFalse($request->pathIs('foo'));
        
        $this->assertTrue($request->pathIs('foo/bar'));
        $this->assertTrue($request->pathIs('/foo/bar'));
        
        $this->assertFalse($request->pathIs('/foo/bar/'));
        
        $this->assertTrue($request->pathIs('/foo/*'));
        
        $this->assertFalse($request->pathIs('/foo/baz', '/foo/biz'));
        $this->assertTrue($request->pathIs('/foo/baz', '/foo/biz', '/foo/bar'));
        
        $request = $this->frontendRequest('GET', '/münchen/foo');
        
        $this->assertTrue($request->pathIs('/münchen/*'));
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
    
    /** @test */
    public function test_isFrontend_throws_exception_if_no_type_is_set()
    {
        $request = new Request($this->psr_request);
        $this->expectExceptionMessage("The request's type attribute");
        $request->isToFrontend();
    }
    
    /** @test */
    public function test_isFrontend_throws_exception_if_type_is_invalid()
    {
        $request = (new Request($this->psr_request))
            ->withAttribute(Request::TYPE_ATTRIBUTE, 'foobar');
        
        try {
            $request->isToFrontend();
            $this->fail('Excepted expected for call to $request->isFrontend()');
        } catch (RequestHasNoType $e) {
            $this->assertSame(
                "The request's type attribute has to be one of [1,2,3].\nGot [string].",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function test_isFrontend_throws_if_type_is_invalid_integer_range()
    {
        $request = new Request(
            $this->psr_request
                ->withAttribute(Request::TYPE_ATTRIBUTE, 4)
        );
        
        try {
            $request->isToFrontend();
            $this->fail('Excepted expected for call to $request->isFrontend()');
        } catch (RequestHasNoType $e) {
            $this->assertSame(
                "The request's type attribute has to be one of [1,2,3].\nGot [4].",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function test_isFrontend()
    {
        $request = new Request(
            $this->psr_request
                ->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_ADMIN_AREA)
        );
        
        $this->assertFalse($request->isToFrontend());
        $this->assertTrue($request->isToAdminArea());
        $this->assertFalse($request->isToApiEndpoint());
        
        $request = $this->request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_FRONTEND);
        
        $this->assertTrue($request->isToFrontend());
    }
    
    /** @test */
    public function test_isAdminArea()
    {
        try {
            $request = new Request($this->psr_request);
            $request->isToAdminArea();
            $this->fail("Expected exception for isAdminArea().");
        } catch (RequestHasNoType $e) {
            $this->assertStringContainsString('type attribute', $e->getMessage());
        }
        
        $request = new Request(
            $this->psr_request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_FRONTEND)
        );
        
        $this->assertTrue($request->isToFrontend());
        $this->assertFalse($request->isToApiEndpoint());
        $this->assertFalse($request->isToAdminArea());
        
        $request = new Request(
            $this->psr_request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_ADMIN_AREA)
        );
        
        $this->assertFalse($request->isToFrontend());
        $this->assertFalse($request->isToApiEndpoint());
        $this->assertTrue($request->isToAdminArea());
    }
    
    /** @test */
    public function test_isApiEndpoint()
    {
        try {
            $request = new Request($this->psr_request);
            $request->isToApiEndpoint();
            $this->fail('Expected exception for isApiEndpoint().');
        } catch (RequestHasNoType $e) {
            $this->assertStringContainsString('type attribute', $e->getMessage());
        }
        
        $request = new Request(
            $this->psr_request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_FRONTEND)
        );
        
        $this->assertTrue($request->isToFrontend());
        $this->assertFalse($request->isToApiEndpoint());
        $this->assertFalse($request->isToAdminArea());
        
        $request = new Request(
            $this->psr_request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_API)
        );
        
        $this->assertFalse($request->isToFrontend());
        $this->assertFalse($request->isToAdminArea());
        $this->assertTrue($request->isToApiEndpoint());
    }
    
    /** @test */
    public function test_ip()
    {
        $request = new Request(
            $this->psrServerRequestFactory()
                 ->createServerRequest('GET', '/foo', ['REMOTE_ADDR' => '12345567'])
        );
        
        $this->assertSame('12345567', $request->ip());
        
        $this->assertSame(null, $this->frontendRequest('GET', '/foo')->ip());
    }
    
    /** @test */
    public function test_from_psr()
    {
        $request = Request::fromPsr(
            $this->psrServerRequestFactory()->createServerRequest('GET', '/foo')
        );
        
        $this->assertSame('/foo', $request->path());
        $this->assertSame('GET', $request->getMethod());
    }
    
}