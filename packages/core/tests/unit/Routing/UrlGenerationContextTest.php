<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Core\Http\Psr7\Request;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Routing\Internal\RequestContext;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

final class UrlGenerationContextTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    private Request $base_request;
    
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
        $context =
            new RequestContext($this->base_request, $d = WPAdminDashboard::fromDefaults());
        
        $this->assertSame('foo.com', $context->getHost());
        $this->assertSame('https', $context->getScheme());
        $this->assertSame(80, $context->getHttpPort());
        $this->assertSame(443, $context->getHttpsPort());
        $this->assertEquals(WPAdminDashboard::fromDefaults(), $context->adminDashboard());
    }
    
    /** @test */
    public function test_is_secure()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withScheme('https');
        
        $context = new RequestContext(
            $this->base_request->withUri($new_uri),
            WPAdminDashboard::fromDefaults()
        );
        $this->assertTrue($context->isSecure());
        
        $context = new RequestContext(
            $this->base_request->withUri($uri->withScheme('http')),
            WPAdminDashboard::fromDefaults()
        );
        $this->assertFalse($context->isSecure());
    }
    
    /** @test */
    public function http_port_return_80_by_default()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withPort(8080);
        
        $context = new RequestContext($this->base_request, WPAdminDashboard::fromDefaults());
        $this->assertSame(80, $context->getHttpPort());
        
        $context = new RequestContext(
            $this->base_request->withUri($new_uri),
            WPAdminDashboard::fromDefaults()
        );
        $this->assertSame(8080, $context->getHttpPort());
    }
    
    /** @test */
    public function http_port_return_443_by_default()
    {
        $uri = $this->base_request->getUri();
        $new_uri = $uri->withPort(4000);
        
        $context = new RequestContext($this->base_request, WPAdminDashboard::fromDefaults());
        $this->assertSame(443, $context->getHttpsPort());
        
        $context = new RequestContext(
            $this->base_request->withUri($new_uri),
            WPAdminDashboard::fromDefaults()
        );
        $this->assertSame(4000, $context->getHttpsPort());
    }
    
    protected function adminDashboard() :AdminDashboard
    {
        return WPAdminDashboard::fromDefaults();
    }
    
}