<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Routing\AbstractRouteCondition;

/**
 * @interal
 */
final class IsAdminDashboardRequest extends AbstractRouteCondition
{
    
    private AdminDashboard $admin_dashboard;
    
    public function __construct(AdminDashboard $admin_path)
    {
        $this->admin_dashboard = $admin_path;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return $this->admin_dashboard->goesTo($request);
    }
    
}