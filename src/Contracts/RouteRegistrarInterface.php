<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Application\Config;

interface RouteRegistrarInterface
{
    
    public function registerRoutes(Config $config) :void;
    
}