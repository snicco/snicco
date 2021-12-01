<?php

declare(strict_types=1);

namespace Tests\Core\integration\EventDispatcher\Events;

use Snicco\EventDispatcher\Events\DoShutdown;
use Tests\Codeception\shared\FrameworkTestCase;

use function add_filter;
use function remove_filter;

class IncomingWebRequest404CompatTest extends FrameworkTestCase
{
    
    protected bool $defer_boot = true;
    
    protected function setUp() :void
    {
        parent::setUp();
        remove_filter('template_redirect', 'redirect_canonical');
        remove_filter('template_redirect', 'remove_old_slug');
    }
    
    /** @test */
    public function if_a_route_matches_the_wp_main_function_will_never_set_a_404_because_our_kernel_terminates_the_script()
    {
        $this->withRequest($this->frontendRequest('GET', '/foo'));
        $this->bootApp();
        $this->simulate404();
        global $wp, $wp_query;
        
        $this->dispatcher->fake(DoShutdown::class);
        
        $wp->main();
        
        $this->dispatcher->assertDispatched(function (DoShutdown $event) {
            return $event->do_shutdown === true;
        });
    }
    
    /** @test */
    public function if_no_route_matched_the_wp_query_is_evaluated_for_a_404()
    {
        $this->withRequest($this->frontendRequest('GET', '/bogus'));
        $this->bootApp();
        $this->simulate404();
        
        global $wp, $wp_query;
        
        $wp->main();
        
        $this->assertTrue($wp_query->is_404());
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