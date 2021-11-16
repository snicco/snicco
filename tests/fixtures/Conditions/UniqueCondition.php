<?php

declare(strict_types=1);

namespace Tests\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class UniqueCondition implements Condition
{
    
    public function isSatisfied(Request $request) :bool
    {
        $count = $GLOBALS['test']['unique_condition'] ?? 0;
        
        $count++;
        
        $GLOBALS['test']['unique_condition'] = $count;
        
        return true;
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}