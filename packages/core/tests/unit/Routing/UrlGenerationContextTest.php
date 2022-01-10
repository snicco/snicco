<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use InvalidArgumentException;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Routing\Internal\UrlGenerationContext;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

final class UrlGenerationContextTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    private ServerRequestInterface $base_request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->base_request = $this->psrServerRequestFactory()
                                   ->createServerRequest('GET', 'https://foo.com');
    }
    
    /** @test */
    public function test_properties()
    {
        $request = $this->frontendRequest('GET', 'https://foobar.com:4000/foo?bar=baz#fragment');
        
        $context = UrlGenerationContext::fromRequest($request);
        
        $this->assertSame('foobar.com', $context->host());
        $this->assertSame('https', $context->currentScheme());
        $this->assertSame(80, $context->httpPort());
        $this->assertSame(4000, $context->httpsPort());
        $this->assertNull($context->referer());
        $this->assertFalse($context->shouldForceHttps());
        $this->assertEquals(
            'https://foobar.com:4000/foo?bar=baz#fragment',
            $context->currentUriAsString()
        );
    }
    
    /** @test */
    public function test_ports_from_request()
    {
        $context = UrlGenerationContext::fromRequest($this->base_request);
        $this->assertSame(80, $context->httpPort());
        $this->assertSame(443, $context->httpsPort());
        
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest('GET', 'http://foo.com:8080/foo');
        
        $context = UrlGenerationContext::fromRequest($request);
        $this->assertSame(8080, $context->httpPort());
        $this->assertSame(443, $context->httpsPort());
        
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest('GET', 'https://foo.com:4000/foo');
        
        $context = UrlGenerationContext::fromRequest($request);
        $this->assertSame(80, $context->httpPort());
        $this->assertSame(4000, $context->httpsPort());
    }
    
    /** @test */
    public function test_exception_for_empty_host()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$host cant be empty');
        
        $request = $this->psrServerRequestFactory()
                        ->createServerRequest('GET', '/foo');
        
        $context = UrlGenerationContext::fromRequest($request);
    }
    
    /** @test */
    public function test_should_force_https_can_be_set()
    {
        $context = UrlGenerationContext::fromRequest($this->base_request, true);
        $this->assertTrue($context->shouldForceHttps());
    }
    
    /** @test */
    public function test_referer_header()
    {
        $context = UrlGenerationContext::fromRequest(
            $this->base_request->withHeader('referer', '/foo/bar')
        );
        
        $this->assertSame('/foo/bar', $context->referer());
    }
    
    /** @test */
    public function test_referer_returns_null_for_empty_string()
    {
        $context = UrlGenerationContext::fromRequest(
            $this->base_request->withHeader('referer', '')
        );
        
        $this->assertSame(null, $context->referer());
    }
    
    /** @test */
    public function test_exception_for_request_scheme_not_http_or_https()
    {
        $this->expectExceptionMessage(
            'The scheme for url generation has to be either http or https.'
        );
        $context = UrlGenerationContext::fromRequest(
            $this->psrServerRequestFactory()->createServerRequest('GET', '//foo.com/foo')
        );
    }
    
    /** @test */
    public function testForConsole()
    {
        $context = UrlGenerationContext::forConsole(
            'foobar.com',
            true,
            443,
            8080,
        );
        
        $this->assertSame('foobar.com', $context->host());
        $this->assertSame('https', $context->currentScheme());
        $this->assertSame(8080, $context->httpPort());
        $this->assertSame(443, $context->httpsPort());
        $this->assertSame(null, $context->referer());
        $this->assertEquals('http://localhost.com/', $context->currentUriAsString());
        $this->assertSame('/', $context->currentPathUrlEncoded());
        $this->assertTrue($context->shouldForceHttps());
        $this->assertTrue($context->isSecure());
    }
    
    /** @test */
    public function test_isSecure()
    {
        $context = UrlGenerationContext::forConsole(
            'foo.com',
            false,
        );
        
        $this->assertFalse($context->isSecure());
        
        $context = UrlGenerationContext::forConsole(
            'foo.com',
        );
        
        $this->assertTrue($context->isSecure());
    }
    
}