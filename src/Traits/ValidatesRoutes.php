<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Snicco\Routing\Route;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

trait ValidatesRoutes
{
    
    public function validateAttributes(Route $route)
    {
        
        if ( ! $route->getAction()) {
            
            throw new ConfigurationException('Tried to register a route with no attached action.');
            
        }
        
    }
    
}