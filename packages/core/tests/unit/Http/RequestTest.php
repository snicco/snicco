<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use RuntimeException;
use Snicco\Support\WP;
use Snicco\Routing\Route;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Support\Repository;
use Snicco\Validation\Validator;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\Drivers\ArraySessionDriver;
use Tests\Core\fixtures\TestDoubles\TestRequest;

class RequestTest extends UnitTest
{
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->request = TestRequest::from('GET', 'foo');
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
        $this->assertSame('/foo/bar', $request->fullPath());
        
        $request = TestRequest::from('GET', '/foo/bar/');
        $this->assertSame('/foo/bar/', $request->fullPath());
        
        $request = TestRequest::from('GET', '/');
        $this->assertSame('/', $request->fullPath());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz');
        $this->assertSame('/foo/bar?baz=biz', $request->fullPath());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar/?baz=biz');
        $this->assertSame('/foo/bar/?baz=biz', $request->fullPath());
        
        $request = TestRequest::fromFullUrl('GET', 'https://foo.com/foo/bar?baz=biz#section');
        $this->assertSame('/foo/bar?baz=biz#section', $request->fullPath());
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
    
    public function testSession()
    {
        try {
            $this->request->session();
            
            $this->fail('Missing session did not throw an exception');
        } catch (RuntimeException $e) {
            $this->assertSame('A session has not been set on the request.', $e->getMessage());
        }
        
        $request = $this->request->withSession($session = new Session(new ArraySessionDriver(10)));
        
        $request = $request->withMethod('POST');
        
        $this->assertSame($session, $request->session());
    }
    
    public function testHasSession()
    {
        $this->assertFalse($this->request->hasSession());
        
        $request = $this->request->withSession(new Session(new ArraySessionDriver(10)));
        
        $this->assertTrue($request->hasSession());
    }
    
    public function testGetLoadingScript()
    {
        $request = TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'index.php']);
        $this->assertSame('index.php', $request->loadingScript());
        
        $request =
            TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'wp-admin/edit.php']);
        $this->assertSame('wp-admin/edit.php', $request->loadingScript());
    }
    
    public function testIsWpAdmin()
    {
        $request = TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'index.php']);
        $this->assertFalse($request->isWpAdmin());
        
        $request =
            TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'wp-admin/edit.php']);
        $this->assertTrue($request->isWpAdmin());
        
        $request = TestRequest::withServerParams(
            $this->request,
            ['SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
        );
        $this->assertFalse($request->isWpAdmin());
    }
    
    public function testIsWpAjax()
    {
        $request = TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'index.php']);
        $this->assertFalse($request->isWpAjax());
        
        $request =
            TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'wp-admin/edit.php']);
        $this->assertFalse($request->isWpAjax());
        
        $request = TestRequest::withServerParams(
            $this->request,
            ['SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
        );
        $this->assertTrue($request->isWpAjax());
    }
    
    public function testisWpFrontEnd()
    {
        $request = TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'index.php']);
        $this->assertTrue($request->isWpFrontend());
        
        $request =
            TestRequest::withServerParams($this->request, ['SCRIPT_NAME' => 'wp-admin/edit.php']);
        $this->assertFalse($request->isWpFrontend());
        
        $request = TestRequest::withServerParams(
            $this->request,
            ['SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
        );
        $this->assertFalse($request->isWpFrontend());
    }
    
    public function testValidator()
    {
        try {
            $this->request->validator();
            
            $this->fail('Missing validator did not throw an exception');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'A validator instance has not been set on the request.',
                $e->getMessage()
            );
        }
        
        $request = $this->request->withValidator($v = new Validator());
        
        $request = $request->withMethod('POST');
        
        $this->assertSame($v, $request->validator());
    }
    
    public function testRouteIs()
    {
        WP::shouldReceive('wpAdminFolder')->andReturn('wp-admin');
        $route = new Route(['GET'], '/foo', function () { });
        $route->name('foobar');
        
        $request = $this->request->withRoute($route);
        
        $this->assertFalse($request->routeIs('bar'));
        $this->assertTrue($request->routeIs('foobar'));
        $this->assertTrue($request->routeIs('bar', 'foobar'));
        $this->assertTrue($request->routeIs(['bar', 'foobar']));
        
        $this->assertTrue($request->routeIs('foo*'));
        
        WP::reset();
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
    
}