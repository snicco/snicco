<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

/**
 * @interal
 */
final class RoutingConfiguratorFactory
{
    
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function create(Router $router) :RoutingConfiguratorUsingRouter
    {
        return new RoutingConfiguratorUsingRouter($router, $this->config);
    }
    
}