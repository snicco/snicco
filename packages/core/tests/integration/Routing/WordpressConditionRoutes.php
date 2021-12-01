<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Snicco\EventDispatcher\Event;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\EventDispatcher\Events\ResponseSent;
use Tests\Core\fixtures\Middleware\WebMiddleware;

class WordpressConditionRoutes extends FrameworkTestCase
{
    
    /** @test */
    public function its_possible_to_create_a_route_without_url_conditions()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = true;
        $this->bootApp();
        Event::fake([ResponseSent::class]);
        
        $response = $this->get('/post1');
        $response->assertOk();
        $response->assertSee('get_condition');
        
        Event::assertDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function routes_with_conditions_have_priority_over_a_user_defined_fallback_routes()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = true;
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        Event::fake([ResponseSent::class]);
        
        $response = $this->get('/post1');
        $response->assertOk();
        $response->assertSee('get_condition');
        
        Event::assertDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function if_no_route_matches_due_to_failed_wp_conditions_a_delegated_response_is_returned()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = false;
        $this->bootApp();
        Event::fake([ResponseSent::class]);
        
        $this->post('/post1')->assertDelegatedToWordPress();
        
        Event::assertNotDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function if_no_route_matches_due_to_different_http_verbs_a_delegated_response_is_returned()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = true;
        $this->bootApp();
        Event::fake([ResponseSent::class]);
        
        $this->delete('/post1')->assertDelegatedToWordPress();
        
        Event::assertNotDispatched(ResponseSent::class);
    }
    
    /** @test */
    public function routes_with_wordpress_conditions_can_have_middleware()
    {
        $GLOBALS['test']['pass_fallback_route_condition'] = true;
        $GLOBALS['test'][WebMiddleware::run_times] = 0;
        $this->bootApp();
        Event::fake([ResponseSent::class]);
        
        $this->patch('/post1')->assertSee('patch_condition');
        
        Event::assertDispatched(ResponseSent::class);
        $this->assertSame(
            1,
            $GLOBALS['test'][WebMiddleware::run_times],
            'Middleware was not run as expected.'
        );
    }
    
}


