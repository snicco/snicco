<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Tests\Codeception\shared\FrameworkTestCase;

class FallBackRouteTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_fallback_route_works_with_trailing_slashes()
    {
        $this->withAddedConfig('routing.trailing_slash', true);
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        
        $response = $this->get('/bogus/');
        $response->assertOk();
        $response->assertSee('FALLBACK');
    }
    
    /** @test */
    public function the_fallback_route_will_not_match_routes_with_trailing_slash_if_no_trailing_slashes_are_used()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        
        $response = $this->get('/bogus/');
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_route_will_not_match_routes_without_trailing_slash_if_trailing_slashes_are_used()
    {
        $this->withAddedConfig('routing.trailing_slash', true);
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        
        $response = $this->get('/bogus');
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_route_works_with_multiple_path_segments()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        
        $response = $this->get('/bogus/bogus');
        $response->assertSee('FALLBACK');
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_robots_text()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        $response = $this->get('robots.txt');
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_sitemap_xml()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        $response = $this->get('sitemap.xml');
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_favicon_ico()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        $this->bootApp();
        $response = $this->get('favicon.ico');
        $response->assertDelegatedToWordPress();
    }
    
    /** @test */
    public function the_fallback_route_is_not_run_for_requests_containing_wp_admin()
    {
        $GLOBALS['test']['include_fallback_route'] = true;
        
        $response = $this->getAdminPage('bogus');
        $response->assertDelegatedToWordPress();
    }
    
}