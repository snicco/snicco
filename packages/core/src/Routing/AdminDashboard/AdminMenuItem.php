<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

use InvalidArgumentException;
use Snicco\Core\Support\UrlPath;
use Snicco\Core\Routing\Route\Route;

/**
 * @api
 */
final class AdminMenuItem
{
    
    public function __construct()
    {
    }
    
    public function fromRoute(Route $route, AdminDashboardPrefix $prefix)
    {
        $pattern = $route->getPattern();
        
        if ( ! UrlPath::fromString($pattern)->startsWith($prefix->asString())) {
            throw new InvalidArgumentException("Only admin routes.");
        }
    }
    
}