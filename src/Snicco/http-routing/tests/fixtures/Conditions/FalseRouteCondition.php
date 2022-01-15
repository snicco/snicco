<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures\Conditions;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Routing\Condition\AbstractRouteCondition;

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