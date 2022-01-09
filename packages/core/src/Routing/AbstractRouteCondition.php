<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;

abstract class AbstractRouteCondition
{
    
    const NEGATE = '!';
    
    /**
     * Get whether the condition is satisfied
     *
     * @param  Request  $request
     *
     * @return boolean
     */
    abstract public function isSatisfied(Request $request) :bool;
    
    /**
     * Get an array of arguments for use in request
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}
