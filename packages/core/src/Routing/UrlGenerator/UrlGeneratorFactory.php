<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\UrlGenerator;

use Snicco\Core\Routing\Route\Routes;
use Snicco\Core\Routing\AdminDashboard\AdminDashboard;

/**
 * @interal
 */
final class UrlGeneratorFactory
{
    
    private UrlGenerationContext $context;
    private AdminDashboard       $admin_dashboard;
    private UrlEncoder           $encoder;
    
    public function __construct(UrlGenerationContext $context, AdminDashboard $admin_dashboard, UrlEncoder $encoder)
    {
        $this->context = $context;
        $this->admin_dashboard = $admin_dashboard;
        $this->encoder = $encoder;
    }
    
    public function create(Routes $routes) :InternalUrlGenerator
    {
        return new InternalUrlGenerator(
            $routes,
            $this->context,
            $this->admin_dashboard,
            $this->encoder
        );
    }
    
}