<?php

declare(strict_types=1);

namespace Tests\integration\Events;

use Snicco\Events\Event;
use Tests\FrameworkTestCase;

class IncomingWebRequest404CompatTest extends FrameworkTestCase
{
    
    protected bool $defer_boot = true;
    
    /** @test */
    public function if_a_route_matches_the_wp_main_function_will_never_set_a_404()
    {
        
        $this->withRequest($this->frontendRequest('GET', '/foo'));
        $this->bootApp();;
        $this->simulate404();
        global $wp, $wp_query;
        
        // In production this will call exit() and no 404 will be processed.
        $did_shutdown = false;
        Event::listen('sniccowp.shutdown', function () use (&$did_shutdown, $wp_query) {
            
            $this->assertFalse($wp_query->is_404());
            $did_shutdown = true;
            
        });
        
        $wp->main();
        
        $this->assertTrue($did_shutdown);
        
    }
    
    /** @test */
    public function if_no_route_matched_the_wp_query_is_evaluated_for_a_404()
    {
        
        $this->withRequest($this->frontendRequest('GET', '/bogus'));
        $this->bootApp();;
        $this->simulate404();
        
        global $wp, $wp_query;
        
        $wp->main();
        
        $this->assertTrue($wp_query->is_404());
        
    }
    
    protected function setUp() :void
    {
        parent::setUp();
        remove_filter('template_redirect', 'redirect_canonical');
        remove_filter('template_redirect', 'remove_old_slug');
    }
    
    private function simulate404()
    {
        add_filter('request', function () {
            
            return [
                'post_type' => 'post',
                'name' => 'bogus-post',
            ];
            
        });
    }
    
}