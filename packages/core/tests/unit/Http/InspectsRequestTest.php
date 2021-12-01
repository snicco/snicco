<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Tests\Codeception\shared\UnitTest;
use Tests\Core\fixtures\TestDoubles\TestRequest;

class InspectsRequestTest extends UnitTest
{
    
    public function testIsGet()
    {
        $request = TestRequest::from('GET', '/foo');
        $this->assertTrue($request->isGet());
        
        $request = TestRequest::from('POST', '/foo');
        $this->assertFalse($request->isGet());
    }
    
    public function testIsPost()
    {
        $request = TestRequest::from('POST', '/foo');
        $this->assertTrue($request->isPost());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isPost());
    }
    
    public function testIsPut()
    {
        $request = TestRequest::from('PUT', '/foo');
        $this->assertTrue($request->isPut());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isPut());
    }
    
    public function testIsPatch()
    {
        $request = TestRequest::from('PATCH', '/foo');
        $this->assertTrue($request->isPatch());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isPatch());
    }
    
    public function testIsOptions()
    {
        $request = TestRequest::from('OPTIONS', '/foo');
        $this->assertTrue($request->isOptions());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isOptions());
    }
    
    public function testIsDelete()
    {
        $request = TestRequest::from('DELETE', '/foo');
        $this->assertTrue($request->isDelete());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isDelete());
    }
    
    public function testIsHead()
    {
        $request = TestRequest::from('HEAD', '/foo');
        $this->assertTrue($request->isHead());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertFalse($request->isHead());
    }
    
    public function testIsSafe()
    {
        $request = TestRequest::from('HEAD', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = TestRequest::from('OPTIONS', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = TestRequest::from('TRACE', '/foo');
        $this->assertTrue($request->isMethodSafe());
        
        $request = TestRequest::from('PUT', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = TestRequest::from('POST', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = TestRequest::from('DELETE', '/foo');
        $this->assertFalse($request->isMethodSafe());
        
        $request = TestRequest::from('PATCH', '/foo');
        $this->assertFalse($request->isMethodSafe());
    }
    
    public function testIsReadVerb()
    {
        $request = TestRequest::from('HEAD', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = TestRequest::from('GET', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = TestRequest::from('OPTIONS', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = TestRequest::from('TRACE', '/foo');
        $this->assertTrue($request->isReadVerb());
        
        $request = TestRequest::from('PUT', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = TestRequest::from('POST', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = TestRequest::from('DELETE', '/foo');
        $this->assertFalse($request->isReadVerb());
        
        $request = TestRequest::from('PATCH', '/foo');
        $this->assertFalse($request->isReadVerb());
    }
    
    public function testIsAjax()
    {
        $request = TestRequest::from('POST', '/foo')
                              ->withAddedHeader('X-Requested-With', 'XMLHttpRequest');
        
        $this->assertTrue($request->isAjax());
        $this->assertTrue($request->isXmlHttpRequest());
        
        $request = TestRequest::from('POST', '/foo');
        
        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isXmlHttpRequest());
    }
    
    public function testIsSendingJson()
    {
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader('Content-Type', 'application/json');
        $this->assertTrue($request->isSendingJson());
        
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader(
                                  'Content-Type',
                                  'application/x-www-form-urlencoded'
                              );
        $this->assertFalse($request->isSendingJson());
    }
    
    public function testWantsJson()
    {
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader('Content-Type', 'application/json')
                              ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->isExpectingJson());
        
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader('Content-Type', 'application/json')
                              ->withAddedHeader('Accept', 'text/html');
        
        $this->assertFalse($request->isExpectingJson());
    }
    
    public function testAccepts()
    {
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->accepts('application/json'));
        $this->assertFalse($request->accepts('text/html'));
        
        $request = TestRequest::from('POST', 'foo');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));
        
        $request = TestRequest::from('POST', 'foo')->withAddedHeader('Accept', '*/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));
        
        $request = TestRequest::from('POST', 'foo')->withAddedHeader('Accept', 'application/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/json+ld'));
        $this->assertFalse($request->accepts('text/html'));
    }
    
    public function testAcceptsOneOf()
    {
        $request = TestRequest::from('POST', 'foo')
                              ->withAddedHeader('Accept', 'application/json');
        
        $this->assertTrue($request->acceptsOneOf(['application/json', 'application/json+ld']));
        $this->assertFalse($request->acceptsOneOf(['text/html', 'application/json+ld']));
    }
    
    public function testAcceptsHtml()
    {
        $request = TestRequest::from('GET', 'foo')->withAddedHeader('Accept', 'text/html');
        
        $this->assertTrue($request->acceptsHtml());
        
        $request = $request->withHeader('Accept', 'text/plain');
        $this->assertFalse($request->acceptsHtml());
    }
    
    public function testGetRealMethod()
    {
        $request = TestRequest::from('GET', 'foo');
        $request = TestRequest::withServerParams($request, ['REQUEST_METHOD' => 'GET']);
        
        $this->assertSame('GET', $request->realMethod());
        
        $request = $request->withMethod('POST');
        
        $this->assertSame('GET', $request->realMethod());
    }
    
}