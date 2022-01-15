<?php

declare(strict_types=1);

namespace Tests\HttpRouting\integration\Http;

use Snicco\HttpRouting\Middleware\Secure;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\HttpRouting\Middleware\WwwRedirect;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\HttpRouting\Middleware\TrailingSlash;
use Snicco\HttpRouting\Middleware\MustMatchRoute;
use Snicco\HttpRouting\Middleware\Internal\RouteRunner;
use Snicco\HttpRouting\Middleware\OpenRedirectProtection;
use Snicco\HttpRouting\Middleware\Internal\MiddlewareStack;
use Snicco\HttpRouting\Middleware\Internal\MiddlewarePipeline;
use Snicco\HttpRouting\Middleware\OutputBufferAbstractMiddleware;

class MiddlewareServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function core_middleware_aliases_are_bound_and_merged_with_user_aliases()
    {
        $aliases = TestApp::config('middleware.aliases');
        
        $this->assertArrayHasKey('auth', $aliases);
        $this->assertArrayHasKey('can', $aliases);
        $this->assertArrayHasKey('guest', $aliases);
        $this->assertArrayHasKey('json', $aliases);
        $this->assertArrayHasKey('robots', $aliases);
        $this->assertArrayHasKey('secure', $aliases);
        
        // from test config
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
        
        $this->assertSame([Secure::class, WwwRedirect::class, TrailingSlash::class], $priority);
    }
    
    /** @test */
    public function middleware_is_not_run_without_matching_routes_by_default()
    {
        $setting = TestApp::config('middleware.always_run_core_groups', '');
        
        $this->assertFalse($setting);
    }
    
    /** @test */
    public function all_middlewares_are_built_correctly()
    {
        $this->assertInstanceOf(
            MustMatchRoute::class,
            TestApp::resolve(MustMatchRoute::class)
        );
        $this->assertInstanceOf(RouteRunner::class, TestApp::resolve(RouteRunner::class));
        $this->assertInstanceOf(MiddlewareStack::class, TestApp::resolve(MiddlewareStack::class));
    }
    
    /** @test */
    public function the_middleware_pipeline_is_not_a_singleton()
    {
        $pipeline1 = TestApp::resolve(MiddlewarePipeline::class);
        $pipeline2 = TestApp::resolve(MiddlewarePipeline::class);
        
        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline1);
        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline2);
        
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
    
    /** @test */
    public function output_buffering_middleware_is_a_singleton()
    {
        $m1 = $this->app->resolve(OutputBufferAbstractMiddleware::class);
        $m2 = $this->app->resolve(OutputBufferAbstractMiddleware::class);
        
        $this->assertSame($m1, $m2);
        $this->assertInstanceOf(OutputBufferAbstractMiddleware::class, $m1);
    }
    
}
