<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class FalseCondition implements Condition
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