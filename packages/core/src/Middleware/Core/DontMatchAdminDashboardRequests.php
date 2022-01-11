<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

final class DontMatchAdminDashboardRequests extends AbstractMiddleware
{
    
    private string $admin_dashboard_prefix;
    
    public function __construct(string $admin_dashboard_prefix)
    {
        $this->admin_dashboard_prefix = trim($admin_dashboard_prefix, '/');
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($request->pathIs("/$this->admin_dashboard_prefix*")) {
            return $this->respond()->delegate(true);
        }
        
        return $next($request);
    }
    
}