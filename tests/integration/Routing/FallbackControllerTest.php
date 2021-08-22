<?php

declare(strict_types=1);

namespace Tests\integration\Routing;

use Snicco\Routing\Router;
use Tests\FrameworkTestCase;
use Tests\fixtures\Middleware\WebMiddleware;
use Tests\fixtures\Middleware\GlobalMiddleware;

class FallbackControllerTest extends FrameworkTestCase
{
    
    private Router $router;
    
    /** @test */
    public function the_fallback_route_will_match_urls_without_trailing_slashes_if_trailing_slashes_are_enforced()
    {
        
        $this->withAddedConfig('routing.trailing_slash', true);
        $this->bootApp();
        $this->router->fallback(fn() => 'fallback');
        
        $response = $this->get('/bogus');
        $response->assertNotNullResponse();
        $response->assertSee('fallback');
        
    }
    
    /** @test */
    public function the_fallback_route_will_match_urls_with_trailing_slashes_if_trailing_are_not_used()
    {
        
        $this->withAddedConfig('routing.trailing_slash', false);
        $this->bootApp();
        $this->router->fallback(fn() => 'fallback');
        
        $response = $this->get('/bogus/');
        $response->assertNotNullResponse();
        $response->assertSee('fallback');
        
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_robots_text()
    {
        
        $this->bootApp();
        $this->router->fallback(fn() => 'foo_fallback');
        $response = $this->get('robots.txt');
        $response->assertDelegatedToWordPress();
        
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_sitemap_xml()
    {
        
        $this->bootApp();
        $this->router->fallback(fn() => 'foo_fallback');
        $response = $this->get('robots.txt');
        $response->assertDelegatedToWordPress();
        
    }
    
    /** @test */
    public function global_middleware_is_not_run_if_the_fallback_controller_does_not_match_a_web_route_and_has_no_user_provided_fallback_route()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        $this->withAddedConfig(['middleware.groups.global' => [GlobalMiddleware::class]]);
        
        $this->get('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'global middleware run for non matching web route.'
        );
        
    }
    
    /** @test */
    public function global_middleware_is_run_if_the_fallback_controller_has_a_fallback_route()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        $this->withAddedConfig(['middleware.groups.global' => [GlobalMiddleware::class]])
             ->bootApp();
        
        $this->router->fallback(fn() => 'FOO_FALLBACK');
        
        $this->get('/bogus')->assertOk()->assertSee('FOO_FALLBACK');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'global middleware not run for non matching web route.'
        );
        
    }
    
    /** @test */
    public function global_middleware_is_not_run_twice_for_fallback_routes_if_nothing_matches()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.global' => [GlobalMiddleware::class],
            'middleware.always_run_global' => true,
        ]);
        
        $this->get('bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'global middleware not run for non matching web route.'
        );
        
    }
    
    /** @test */
    public function global_middleware_is_not_run_twice_if_a_user_defined_fallback_route_exists()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.global' => [GlobalMiddleware::class],
            'middleware.always_run_global' => true,
        ])->bootApp();
        
        $this->router->fallback(fn() => 'FOO_FALLBACK');
        
        $this->get('bogus')->assertOk()->assertSee('FOO_FALLBACK');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'global middleware not run for non matching web route.'
        );
        
    }
    
    /** @test */
    public function web_middleware_is_run_for_non_matching_routes_if_middleware_is_run_globally()
    {
        
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.web' => [WebMiddleware::class],
            'middleware.always_run_global' => true,
        ])->bootApp();
        
        $this->get('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][WebMiddleware::run_times],
            'web middleware not run when it was expected.'
        );
        
    }
    
    /** @test */
    public function web_middleware_is_not_run_for_non_matching_routes_when_middleware_is_not_run_globally()
    {
        
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.web' => [WebMiddleware::class],
            'middleware.always_run_global' => false,
        ])->bootApp();
        
        $this->get('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][WebMiddleware::run_times],
            'web middleware run when it was not expected.'
        );
        
    }
    
    protected function setUp() :void
    {
        
        $this->afterApplicationBooted(function () {
            $this->router = $this->app->resolve(Router::class);
        });
        
        parent::setUp();
        
    }
    
}