<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Http\Exceptions\RequestHasNoType;
use Snicco\Core\Routing\AdminDashboard\AdminDashboard;

/**
 * @api
 */
final class TagRequest extends AbstractMiddleware
{
    
    private AdminDashboard $admin_dashboard;
    
    public function __construct(AdminDashboard $admin_dashboard)
    {
        $this->admin_dashboard = $admin_dashboard;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        try {
            $api = $request->isApiEndpoint();
            
            if ($api) {
                return $next($request);
            }
        } catch (RequestHasNoType $e) {
            // No attribute has been explicitly.
        }
        
        if ($this->admin_dashboard->goesTo($request)) {
            return $next(
                $request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_ADMIN_AREA)
            );
        }
        
        return $next($request->withAttribute(Request::TYPE_ATTRIBUTE, Request::TYPE_FRONTEND));
    }
    
}