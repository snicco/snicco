<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\AbstractRouteCondition;

class TrueRouteCondition extends AbstractRouteCondition
{
    
    public function isSatisfied(Request $request) :bool
    {
        return true;
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}