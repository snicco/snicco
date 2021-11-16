<?php

declare(strict_types=1);

namespace Tests\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConditionInterface;

class FalseCondition implements ConditionInterface
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