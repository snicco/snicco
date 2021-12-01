<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Conditions\IsPost;

class FallbackRouteTest extends RoutingTestCase
{
    
    /** @test */
    public function users_can_create_a_custom_fallback_route_that_gets_run_if_no_route_matched_at_all()
    {
        $this->createRoutes(function () {
            $this->router->get()->where(IsPost::class, false)
                         ->handle(function () {
                             return 'FOO';
                         });
            
            $this->router->fallback(function () {
                return 'FOO_FALLBACK';
            });
        });
        
        $request = $this->frontendRequest('GET', 'post1');
        $this->assertResponse('FOO_FALLBACK', $request);
    }
    
}