<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Core\DontMatchAdminDashboardRequests;

final class DontMatchAdminDashboardRequestsTest extends MiddlewareTestCase
{
    
    /** @test */
    public function test_next_is_not_called_for_requests_to_wp_admin()
    {
        $m = new DontMatchAdminDashboardRequests('wp-admin');
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest('GET', 'wp-admin/foo')
        );
        
        $response = $this->runMiddleware($m, $request);
        
        $response->assertNextMiddlewareNotCalled();
    }
    
    /** @test */
    public function test_next_is_called_for_non_admin_dashboard_requests()
    {
        $m = new DontMatchAdminDashboardRequests('wp-admin');
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest('GET', 'foo-admin/foo')
        );
        
        $response = $this->runMiddleware($m, $request);
        
        $response->assertNextMiddlewareCalled();
    }
    
}