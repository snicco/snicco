<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Application\Config;

interface RouteRegistrar
{
    
    public function registerRoutes(Config $config) :void;
    
}