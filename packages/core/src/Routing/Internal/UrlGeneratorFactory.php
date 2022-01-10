<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Core\Routing\Routes;
use Snicco\Core\Routing\UrlEncoder;
use Snicco\Core\Routing\AdminDashboard;

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
    
    public function create(Routes $routes) :Generator
    {
        return new Generator(
            $routes,
            $this->context,
            $this->admin_dashboard,
            $this->encoder
        );
    }
    
}