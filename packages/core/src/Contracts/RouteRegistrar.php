<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Snicco\Core\Configuration\WritableConfig;

interface RouteRegistrar
{
    
    public function registerRoutes(WritableConfig $config) :void;
    
}