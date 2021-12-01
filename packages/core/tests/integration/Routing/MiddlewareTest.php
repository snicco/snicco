<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Tests\Codeception\shared\FrameworkTestCase;
use Tests\Core\fixtures\Middleware\WebMiddleware;
use Tests\Core\fixtures\Middleware\GlobalMiddleware;

class MiddlewareTest extends FrameworkTestCase
{
    
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
             ->withAddedConfig(['middleware.always_run_core_groups' => true]);
        
        $this->get('middleware/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run as expected'
        );
    }
    
    /** @test */
    public function global_middleware_that_always_runs_that_also_is_route_middleware_is_not_run_twice()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->withAddedMiddleware('global', GlobalMiddleware::class)
             ->withAddedConfig(['middleware.always_run_core_groups' => true]);
        
        $this->get('/middleware/route-with-global')
             ->assertSee('route-with-global');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'Middleware was not run as expected.'
        );
    }
    
    /** @test */
    public function global_middleware_is_run_if_a_fallback_route_exists()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->withAddedConfig(['middleware.groups.global' => [GlobalMiddleware::class]])
             ->bootApp();
        
        $this->get('/bogus')->assertOk()->assertSee('FALLBACK');
        
        $this->assertSame(
            1,
            $GLOBALS['test'][GlobalMiddleware::run_times],
            'global middleware not run for non matching web route.'
        );
    }
    
    /** @test */
    public function web_middleware_is_not_run_for_non_matching_web_routes_by_default()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.web' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => false,
        ])->bootApp();
        
        $this->get('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][WebMiddleware::run_times],
            'web middleware was run unexpectedly.'
        );
    }
    
    /** @test */
    public function web_middleware_can_be_enabled_to_run_for_non_matching_frontend_requests()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.web' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => true,
        ]);
        
        $this->get('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][WebMiddleware::run_times],
            'web middleware was not run.'
        );
    }
    
    /** @test */
    public function admin_middleware_is_not_run_for_non_matching_admin_routes()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.admin' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => false,
        ])->bootApp();
        
        $this->getAdminPage('/bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][WebMiddleware::run_times],
            'middleware was run unexpectedly for admin requests.'
        );
    }
    
    /** @test */
    public function admin_middleware_can_be_enabled_to_run_for_non_matching_frontend_requests()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.admin' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => true,
        ]);
        
        $this->getAdminPage('bogus')->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][WebMiddleware::run_times],
            'admin middleware was not run.'
        );
    }
    
    /** @test */
    public function ajax_middleware_is_not_run_for_non_matching_ajax_routes()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.ajax' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => false,
        ])->bootApp();
        
        $this->getAdminAjax(['action' => 'bogus'], [])->assertDelegatedToWordPress();
        
        $this->assertSame(
            0,
            $GLOBALS['test'][WebMiddleware::run_times],
            'middleware was run unexpectedly for admin requests.'
        );
    }
    
    /** @test */
    public function ajax_middleware_can_be_enabled_to_run_for_non_matching_frontend_requests()
    {
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        
        $this->withAddedConfig([
            'middleware.groups.ajax' => [WebMiddleware::class],
            'middleware.always_run_core_groups' => true,
        ]);
        
        $this->getAdminAjax(['action' => 'bogus'])->assertDelegatedToWordPress();
        
        $this->assertSame(
            1,
            $GLOBALS['test'][WebMiddleware::run_times],
            'ajax middleware was not run.'
        );
    }
    
}