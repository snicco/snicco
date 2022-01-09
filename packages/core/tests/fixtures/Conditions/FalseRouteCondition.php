<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\AbstractRouteCondition;

class FalseRouteCondition extends AbstractRouteCondition
{
    
    public function isSatisfied(Request $request) :bool
    {
        return false;
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}