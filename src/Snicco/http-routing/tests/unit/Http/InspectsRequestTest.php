<?php

declare(strict_types=1);

namespace Tests\HttpRouting\unit\Http;

use Tests\Codeception\shared\UnitTest;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class InspectsRequestTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    public function testIsGet()
    {
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertTrue($request->isGet());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isGet());
    }
    
    public function testIsPost()
    {
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertTrue($request->isPost());
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertFalse($request->isPost());
    }
    
    public function testIsPut()
    {
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertTrue($request->isPut());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isPut());
    }
    
    public function testIsPatch()
    {
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertTrue($request->isPatch());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isPatch());
    }
    
    public function testIsOptions()
    {
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertTrue($request->isOptions());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isOptions());
    }
    
    public function testIsDelete()
    {
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertTrue($request->isDelete());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isDelete());
    }
    
    public function testIsHead()
    {
        $request = $this->frontendRequest('HEAD', '/foo');
        $this->assertTrue($request->isHead());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isHead());
    }
    
    public function testIsSafe()
    {
        $request = $this->frontendRequest('HEAD', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = $this->frontendRequest('TRACE', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertFalse($request->isMethodSafe());
    }
    
    public function testIsReadVerb()
    {
        $request = $this->frontendRequest('HEAD', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = $this->frontendRequest('TRACE', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertFalse($request->isReadVerb());
    }
    
    public function testIsAjax()
    {
        $request = $this->frontendRequest('POST', '/foo')
                        ->withAddedHeader('X-Requested-With', 'XMLHttpRequest');
        
        $this->assertTrue($request->isAjax());
        $this->assertTrue($request->isXmlHttpRequest());
        
        $request = $this->frontendRequest('POST', '/foo');
        
        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isXmlHttpRequest());
    }
    
    public function testIsSendingJson()
    {
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader('Content-Type', 'application/json');
        $this->assertTrue($request->isSendingJson());
        
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader(
                            'Content-Type',
                            'application/x-www-form-urlencoded'
                        );
        $this->assertFalse($request->isSendingJson());
    }
    
    public function testWantsJson()
    {
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader('Content-Type', 'application/json')
                        ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->isExpectingJson());
        
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader('Content-Type', 'application/json')
                        ->withAddedHeader('Accept', 'text/html');
        
        $this->assertFalse($request->isExpectingJson());
    }
    
    public function testAccepts()
    {
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->accepts('application/json'));
        $this->assertFalse($request->accepts('text/html'));
        
        $request = $this->frontendRequest('POST', 'foo');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));
        
        $request = $this->frontendRequest('POST', 'foo')->withAddedHeader('Accept', '*/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));
        
        $request =
            $this->frontendRequest('POST', 'foo')->withAddedHeader('Accept', 'application/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/json+ld'));
        $this->assertFalse($request->accepts('text/html'));
    }
    
    public function testAcceptsOneOf()
    {
        $request = $this->frontendRequest('POST', 'foo')
                        ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->acceptsOneOf(['application/json', 'application/json+ld']));
        $this->assertFalse($request->acceptsOneOf(['text/html', 'application/json+ld']));
    }
    
    public function testAcceptsHtml()
    {
        $request = $this->frontendRequest('GET', 'foo')->withAddedHeader('Accept', 'text/html');
        
        $this->assertTrue($request->acceptsHtml());
        
        $request = $request->withHeader('Accept', 'text/plain');
        $this->assertFalse($request->acceptsHtml());
    }
    
    public function testGetRealMethod()
    {
        $request = $this->frontendRequest('GET', 'foo', ['REQUEST_METHOD' => 'GET']);
        
        $this->assertSame('GET', $request->realMethod());
        
        $request = $request->withMethod('POST');
        
        $this->assertSame('GET', $request->realMethod());
    }
    
}