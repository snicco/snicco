<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Core\Routing\AdminDashboardPrefix;

/**
 * @interal
 */
final class RoutingConfiguratorFactory
{
    
    private array                $config;
    private AdminDashboardPrefix $prefix;
    
    public function __construct(AdminDashboardPrefix $prefix, array $config)
    {
        $this->prefix = $prefix;
        $this->config = $config;
    }
    
    public function create(Router $router) :RoutingConfiguratorUsingRouter
    {
        return new RoutingConfiguratorUsingRouter($router, $this->prefix, $this->config);
    }
    
}