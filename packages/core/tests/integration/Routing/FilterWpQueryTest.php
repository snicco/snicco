<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use WP;
use Tests\Codeception\shared\FrameworkTestCase;

class FilterWpQueryTest extends FrameworkTestCase
{
    
    /** @test */
    public function WP_QUERY_vars_can_be_filtered_by_a_route()
    {
        $this->withRequest($this->frontendRequest('GET', '/wpquery/foo'));
        $this->bootApp();
        
        global $wp;
        
        $wp->main();
        
        $this->assertSame(['foo' => 'baz'], $wp->query_vars);
        
        $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');
    }
    
    /** @test */
    public function the_query_can_ONLY_get_filtered_for_read_verbs()
    {
        // The route responds to post but the event won't get dispatched.
        $this->withRequest($this->frontendRequest('POST', '/wpquery/post'))->bootApp();
        
        /** @var WP $wp */
        global $wp;
        
        $wp->main();
        
        $this->assertSame([], $wp->query_vars);
        
        $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');
    }
    
    /** @test */
    public function captured_route_params_get_passed_to_the_query_filter()
    {
        $this->withoutExceptionHandling();
        
        $this->withRequest($this->frontendRequest('GET', '/wpquery/teams/germany/dortmund'))
             ->bootApp();
        
        /** @var WP $wp */
        global $wp;
        $wp->main();
        
        $this->assertSame(['germany' => 'dortmund'], $wp->query_vars);
        $this->sentResponse()->assertSee('germany.dortmund')->assertOk();
    }
    
    /** @test */
    public function the_route_handler_does_not_get_run_when_filtering_WP_QUERY()
    {
        $this->withRequest($this->frontendRequest('GET', '/wpquery/assert-no-driver-run'))
             ->bootApp();
        
        global $wp;
        $wp->parse_request();
        
        $this->assertSame(['foo' => 'baz'], $wp->query_vars);
        
        $this->assertNoResponse();
    }
    
    /** @test */
    public function its_possible_to_create_routes_that_ONLY_CHANGE_WP_QUERY_but_dont_have_a_route_action()
    {
        $this->withRequest($this->frontendRequest('GET', '/wpquery/do-nothing'))->bootApp();
        
        global $wp;
        $wp->main();
        
        $this->assertSame(['foo' => 'baz'], $wp->query_vars);
        
        $this->sentResponse()->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_WP_QUERY_parsing_flow_remains_the_same_if_no_custom_route_matched()
    {
        $this->withRequest($this->frontendRequest('GET', '/wpquery/bogus'))->bootApp();
        
        $request_parsed = false;
        add_action('request', function ($query_vars) use (&$request_parsed) {
            $request_parsed = true;
            
            return $query_vars;
        });
        
        global $wp;
        $wp->main();
        
        $this->assertTrue($request_parsed);
    }
    
    /** @test */
    public function the_WP_QUERY_flow_is_short_circuited_if_a_custom_route_matched()
    {
        $this->withRequest($this->frontendRequest('GET', '/wpquery/foo'))->bootApp();
        
        $request_parsed = false;
        add_action('request', function () use (&$request_parsed) {
            $request_parsed = true;
        });
        
        global $wp;
        $wp->main();
        
        $this->assertFalse($request_parsed);
    }
    
}