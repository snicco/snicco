<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class NegateCondition implements Condition
{
    
    private Condition $condition;
    
    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return ! $this->condition->isSatisfied($request);
    }
    
    public function getArguments(Request $request) :array
    {
        return $this->condition->getArguments($request);
    }
    
}
