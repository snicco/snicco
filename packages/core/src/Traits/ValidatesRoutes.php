<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Snicco\Routing\Route;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

trait ValidatesRoutes
{
    
    private function validateAttributes(Route $route)
    {
        if ( ! $route->getAction()) {
            throw new ConfigurationException('Tried to register a route with no attached action.');
        }
        
        if ($route->routableByUrl() && $route->routableByCondition()) {
            throw new ConfigurationException(
                "Route that uses the pattern [{$route->getUrl()}] also uses custom conditions."
            );
        }
        
        if ($route->routableByCondition() && $route->wantsToFilterWPQuery()) {
            throw new ConfigurationException(
                'It is not possible for a route to filter the WP_QUERY and use conditional tags for matching.'
            );
        }
    }
    
}