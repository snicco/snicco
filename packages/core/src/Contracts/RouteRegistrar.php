<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Snicco\Core\Application\Config;

interface RouteRegistrar
{
    
    public function registerRoutes(Config $config) :void;
    
}