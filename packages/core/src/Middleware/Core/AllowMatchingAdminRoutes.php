<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Contracts\AbstractMiddleware;

final class AllowMatchingAdminRoutes extends AbstractMiddleware
{
    
    const REWRITTEN_URI = 'routing_admin_uri_rewritten';
    private AdminDashboard $admin_dashboard;
    
    public function __construct(AdminDashboard $admin_dashboard)
    {
        $this->admin_dashboard = $admin_dashboard;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $uri = $request->getUri();
        
        $new_path = $this->appendToPath($request);
        
        $new_uri = $uri->withPath($new_path);
        
        return $next($request->withAttribute(self::REWRITTEN_URI, $new_uri));
    }
    
    private function appendToPath(Request $request) :string
    {
        $path = $request->path();
        
        if ( ! $request->isGet()) {
            return $path;
        }
        
        if ($this->admin_dashboard->goesTo($request)) {
            $path = $this->admin_dashboard->rewriteForRouting($request);
        }
        
        return $path;
    }
    
}