<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class TrueCondition implements Condition
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