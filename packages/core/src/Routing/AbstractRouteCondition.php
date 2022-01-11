<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;

abstract class AbstractRouteCondition
{
    
    const NEGATE = '!';
    
    abstract public function isSatisfied(Request $request) :bool;
    
    /**
     * Get an array of arguments that will be merged with the url segments and passed to the
     * controller.
     *
     * @param  Request  $request
     *
     * @return array<mixed>
     */
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}
