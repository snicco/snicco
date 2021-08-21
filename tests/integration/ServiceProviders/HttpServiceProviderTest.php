<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Snicco\Http\HttpKernel;
use Snicco\Http\Redirector;
use Tests\FrameworkTestCase;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\AbstractRedirector;

class HttpServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_kernel_can_be_resolved_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(HttpKernel::class, TestApp::resolve(HttpKernel::class));
    }
    
    /** @test */
    public function the_response_factory_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(ResponseFactory::class, TestApp::resolve(ResponseFactory::class));
    }
    
    /** @test */
    public function the_redirector_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(Redirector::class, TestApp::resolve(AbstractRedirector::class));
    }
    
}

