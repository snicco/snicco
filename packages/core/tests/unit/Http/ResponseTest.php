<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Mockery;
use Snicco\Http\Cookie;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class ResponseTest extends UnitTest
{
    
    use CreatePsr17Factories;
    
    private ResponseFactory $factory;
    private Response        $response;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->factory = $this->createResponseFactory();
        $this->response = $this->factory->make();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    public function testIsPsrResponse()
    {
        $response = $this->factory->createResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
    
    public function testIsImmutable()
    {
        $response1 = $this->factory->make();
        $response2 = $response1->withHeader('foo', 'bar');
        
        $this->assertNotSame($response1, $response2);
        $this->assertTrue($response2->hasHeader('foo'));
        $this->assertFalse($response1->hasHeader('foo'));
    }
    
    public function testNonPsrAttributesAreCopiedForNewInstances()
    {
        $response1 = $this->factory->createResponse();
        $response1->foo = 'bar';
        
        $response2 = $response1->withHeader('foo', 'bar');
        
        $this->assertNotSame($response1, $response2);
        $this->assertTrue($response2->hasHeader('foo'));
        $this->assertFalse($response1->hasHeader('foo'));
        
        $this->assertSame('bar', $response1->foo);
        $this->assertSame('bar', $response2->foo);
    }
    
    public function testHtml()
    {
        $stream = $this->factory->createStream('foo');
        
        $response = $this->factory->make()->html($stream);
        
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
        $this->assertSame('foo', $response->getBody()->__toString());
    }
    
    public function testJson()
    {
        $stream = $this->factory->createStream(json_encode(['foo' => 'bar']));
        
        $response = $this->factory->make()->json($stream);
        
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(['foo' => 'bar'], json_decode($response->getBody()->__toString(), true));
    }
    
    public function testNoIndex()
    {
        $response = $this->response->noIndex();
        $this->assertSame('noindex', $response->getHeaderLine('x-robots-tag'));
        
        $response = $this->response->noIndex('googlebot');
        $this->assertSame('googlebot: noindex', $response->getHeaderLine('x-robots-tag'));
    }
    
    public function testNoFollow()
    {
        $response = $this->response->noFollow();
        $this->assertSame('nofollow', $response->getHeaderLine('x-robots-tag'));
        
        $response = $this->response->noFollow('googlebot');
        $this->assertSame('googlebot: nofollow', $response->getHeaderLine('x-robots-tag'));
    }
    
    public function testNoRobots()
    {
        $response = $this->response->noRobots();
        $this->assertSame('none', $response->getHeaderLine('x-robots-tag'));
        
        $response = $this->response->noRobots('googlebot');
        $this->assertSame('googlebot: none', $response->getHeaderLine('x-robots-tag'));
    }
    
    public function testNoArchive()
    {
        $response = $this->response->noArchive();
        $this->assertSame('noarchive', $response->getHeaderLine('x-robots-tag'));
        
        $response = $this->response->noArchive('googlebot');
        $this->assertSame('googlebot: noarchive', $response->getHeaderLine('x-robots-tag'));
    }
    
    public function testIsInformational()
    {
        $response = $this->response->withStatus(100);
        $this->assertTrue($response->isInformational());
        $this->assertTrue($response->withStatus(199)->isInformational());
        $this->assertFalse($response->withStatus(200)->isInformational());
    }
    
    /** @test */
    public function testIsRedirection()
    {
        $response = $this->response->withStatus(299);
        $this->assertFalse($response->isRedirection());
        $this->assertTrue($response->withStatus(300)->isRedirection());
        $this->assertFalse($response->withStatus(400)->isRedirection());
    }
    
    /** @test */
    public function testIsClientError()
    {
        $response = $this->response->withStatus(399);
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->withStatus(400)->isClientError());
        $this->assertFalse($response->withStatus(500)->isClientError());
    }
    
    /** @test */
    public function testIsServerError()
    {
        $response = $this->response->withStatus(499);
        $this->assertFalse($response->isServerError());
        $this->assertTrue($response->withStatus(500)->isServerError());
        $this->assertTrue($response->withStatus(599)->isServerError());
    }
    
    /** @test */
    public function testHasEmptyBody()
    {
        $response = $this->factory->make();
        $this->assertTrue($response->hasEmptyBody());
        
        $response->getBody()->detach();
        $this->assertTrue($response->hasEmptyBody());
        
        $html_response = $this->factory->html('foobar');
        $this->assertFalse($html_response->hasEmptyBody());
    }
    
    /** @test */
    public function testIsEmpty()
    {
        $response = $this->response->withStatus(204);
        $this->assertTrue($response->isEmpty());
        $this->assertTrue($response->withStatus(304)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(204)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(304)->isEmpty());
    }
    
    /** @test */
    public function testCookiesAreNotResetInNestedResponses()
    {
        $redirect_response = $this->factory->createResponse()->withCookie(new Cookie('foo', 'bar'));
        
        $response = new Response($redirect_response);
        
        $response = $response->withCookie(new Cookie('bar', 'baz'));
        
        $cookies = $response->cookies();
        
        $headers = $cookies->toHeaders();
        
        $this->assertCount(2, $headers);
        $this->assertStringStartsWith('foo=bar', $headers[0]);
        $this->assertStringStartsWith('bar=baz', $headers[1]);
    }
    
    protected function newUrlGenerator(Request $request = null, bool $trailing_slash = false) :UrlGenerator
    {
        return Mockery::mock(UrlGenerator::class);
    }
    
}