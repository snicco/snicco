<?php

declare(strict_types=1);

namespace Tests\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConditionInterface;

class TrueCondition implements ConditionInterface
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