<?php

declare(strict_types=1);

namespace Tests\unit\Http;

use Tests\UnitTest;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseFactory;
use Tests\helpers\CreateUrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Tests\helpers\CreateRouteCollection;

class ResponseTest extends UnitTest
{
    
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    private ResponseFactory $factory;
    private Response        $response;
    
    public function testIsPsrResponse()
    {
        
        $response = $this->factory->createResponse();
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertNotInstanceOf(Response::class, $response);
        
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
    
    public function testIsEmpty()
    {
        
        $response = $this->response->withStatus(204);
        $this->assertTrue($response->isEmpty());
        $this->assertTrue($response->withStatus(304)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(204)->isEmpty());
        $this->assertTrue($this->factory->html('foo')->withStatus(304)->isEmpty());
        
    }
    
    public function testWithContentType()
    {
        
        $response = $this->factory->make()->withContentType('text/html');
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
        
    }
    
    public function testWithEmptyBodyTest()
    {
        
        $response1 = $this->factory->html('foo');
        $this->assertSame(3, $response1->getBody()->getSize());
        
        $response2 = $response1->withEmptyBody();
        $this->assertSame(0, $response2->getBody()->getSize());
        $this->assertSame(3, $response1->getBody()->getSize());
        
        // alias
        $response3 = $response1->withoutBody();
        $this->assertSame(0, $response3->getBody()->getSize());
        $this->assertSame(3, $response1->getBody()->getSize());
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        
        $this->factory = $this->createResponseFactory();
        $this->response = $this->factory->make();
    }
    
}