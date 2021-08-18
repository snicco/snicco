<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\TestCase;
use Tests\stubs\TestApp;
use Snicco\Middleware\Www;
use Snicco\Routing\Pipeline;
use Snicco\Middleware\Secure;
use Snicco\Middleware\TrailingSlash;
use Snicco\Middleware\MiddlewareStack;
use Snicco\Middleware\Core\RouteRunner;
use Snicco\Middleware\Core\OpenRedirectProtection;
use Snicco\Middleware\Core\EvaluateResponseMiddleware;

class MiddlewareServiceProviderTest extends TestCase
{
    
    /** @test */
    public function middleware_aliases_are_bound()
    {
        
        $aliases = TestApp::config('middleware.aliases');
        
        $this->assertArrayHasKey('auth', $aliases);
        $this->assertArrayHasKey('can', $aliases);
        $this->assertArrayHasKey('guest', $aliases);
        $this->assertArrayHasKey('json', $aliases);
        $this->assertArrayHasKey('robots', $aliases);
        $this->assertArrayHasKey('secure', $aliases);
        $this->assertArrayHasKey('signed', $aliases);
        $this->assertArrayHasKey('foo', $aliases);
        
    }
    
    /** @test */
    public function the_middleware_groups_are_extended_empty()
    {
        
        $groups = TestApp::config('middleware.groups');
        
        $this->assertSame([], $groups['web']);
        $this->assertSame([], $groups['ajax']);
        $this->assertSame([], $groups['admin']);
        $this->assertSame([], $groups['global']);
        
    }
    
    /** @test */
    public function the_middleware_priority_is_extended()
    {
        
        $priority = TestApp::config('middleware.priority');
        
        $this->assertSame([Secure::class, Www::class, TrailingSlash::class], $priority);
        
    }
    
    /** @test */
    public function middleware_is_not_run_without_matching_routes_by_default()
    {
        
        $setting = TestApp::config('middleware.always_run_global', '');
        
        $this->assertFalse($setting);
        
    }
    
    /** @test */
    public function all_services_are_built_correctly()
    {
        
        $this->assertInstanceOf(
            EvaluateResponseMiddleware::class,
            TestApp::resolve(EvaluateResponseMiddleware::class)
        );
        $this->assertInstanceOf(RouteRunner::class, TestApp::resolve(RouteRunner::class));
        $this->assertInstanceOf(MiddlewareStack::class, TestApp::resolve(MiddlewareStack::class));
        
    }
    
    /** @test */
    public function the_middleware_pipeline_is_not_a_singleton()
    {
        
        $pipeline1 = TestApp::resolve(Pipeline::class);
        $pipeline2 = TestApp::resolve(Pipeline::class);
        
        $this->assertInstanceOf(Pipeline::class, $pipeline1);
        $this->assertInstanceOf(Pipeline::class, $pipeline2);
        
        $this->assertNotSame($pipeline1, $pipeline2);
        
    }
    
    /** @test */
    public function the_open_redirect_middleware_can_be_resolved()
    {
        
        $this->assertInstanceOf(
            OpenRedirectProtection::class,
            TestApp::resolve(OpenRedirectProtection::class)
        );
        
    }
    
}
