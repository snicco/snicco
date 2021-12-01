<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\Routing\Route;

interface RouteBinding
{
    
    public function substitute(Route $route, array $current_values) :array;
    
}