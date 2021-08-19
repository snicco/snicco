<?php

declare(strict_types=1);

namespace Tests\integration\Http;

use Tests\TestCase;
use Tests\fixtures\Middleware\WebMiddleware;
use Tests\fixtures\Middleware\GlobalMiddleware;

class HttpKernelMiddlewareTest extends TestCase
{
    
    protected bool $defer_boot = true;
    
    /** @test */
    public function custom_middleware_groups_can_be_defined()
    {
        
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('custom_group', WebMiddleware::class);
        
        $this->get('/middleware/foo')->assertSee('foo');
        
        $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times]);
        
    }
    
    /** @test */
    public function global_middleware_is_run_when_a_route_matches()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class);
        
        $this->get('/foo')->assertSee('foo');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run but was expected to.'
        );
        
    }
    
    /** @test */
    public function global_middleware_is_not_run_by_default_if_no_route_matches()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class);
        
        $this->get('middleware/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was run unexpectedly.'
        );
        
    }
    
    /** @test */
    public function global_middleware_can_be_enabled_to_run_always_even_without_matching_a_route()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class)
             ->withAddedConfig(['middleware.always_run_global' => true]);
        
        $this->get('middleware/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run as expected'
        );
        
    }
    
    /** @test */
    public function global_middleware_is_not_run_twice_for_matching_url_routes()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class)
             ->withAddedConfig(['middleware.always_run_global' => true]);
        
        $this->get('/foo')->assertSee('foo');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run as expected.'
        );
        
    }
    
    /** @test */
    public function global_middleware_that_always_runs_that_also_is_route_middleware_is_not_run_twice()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class)
             ->withAddedConfig(['middleware.always_run_global' => true]);
        
        $this->get('/middleware/route-with-global')
             ->assertSee('route-with-global');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run as expected.'
        );
        
    }
    
}