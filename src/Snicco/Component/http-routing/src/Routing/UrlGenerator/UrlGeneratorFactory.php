<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminArea;

/**
 * @api
 */
final class UrlGeneratorFactory
{
    
    private UrlGenerationContext $context;
    private AdminArea            $admin_dashboard;
    private UrlEncoder           $encoder;
    
    public function __construct(UrlGenerationContext $context, AdminArea $admin_dashboard, UrlEncoder $encoder)
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