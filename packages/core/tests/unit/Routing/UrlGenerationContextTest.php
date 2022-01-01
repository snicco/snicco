<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Core\Http\Psr7\Request;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Routing\UrlGenerationContext;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

final class UrlGenerationContextTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    /**
     * @var Request
     */
    private $base_request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->base_request = new Request(
            $this->psrServerRequestFactory()->createServerRequest('GET', 'https://foo.com')
        );
    }
    
    /** @test */
    public function test_properties()
    {
        $context = new UrlGenerationContext($this->base_request);
        
        $this->assertSame('foo.com', $context->getHost());
        $this->assertSame('https', $context->getScheme());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame(443, $context->getHttpsPort());
    }
    
    /** @test */
    public function test_is_secure()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withScheme('https');
        
        $context = new UrlGenerationContext($this->base_request->withUri($new_uri));
        $this->assertTrue($context->isSecure());
        
        $context = new UrlGenerationContext($this->base_request->withUri($uri->withScheme('http')));
        $this->assertFalse($context->isSecure());
    }
    
    /** @test */
    public function http_port_return_80_by_default()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withPort(8080);
        
        $context = new UrlGenerationContext($this->base_request);
        $this->assertSame(80, $context->getHttpPort());
        
        $context = new UrlGenerationContext($this->base_request->withUri($new_uri));
        $this->assertSame(8080, $context->getHttpPort());
    }
    
    /** @test */
    public function http_port_return_443_by_default()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withPort(4000);
        
        $context = new UrlGenerationContext($this->base_request);
        $this->assertSame(443, $context->getHttpsPort());
        
        $context = new UrlGenerationContext($this->base_request->withUri($new_uri));
        $this->assertSame(4000, $context->getHttpsPort());
    }
    
}