<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class FallbackRouteTest extends RoutingTestCase
{
    
    /** @test */
    public function users_can_create_a_custom_fallback_web_route()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);
        
        $request = $this->frontendRequest('GET', '/bar');
        $this->assertResponseBody('fallback:bar', $request);
        
        $request = $this->frontendRequest('GET', '/bar/baz');
        $this->assertResponseBody('fallback:bar/baz', $request);
    }
    
}