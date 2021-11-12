<?php

declare(strict_types=1);

namespace Tests\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ConditionInterface;

class IsPost implements ConditionInterface
{
    
    private bool $pass;
    
    public function __construct(bool $pass = true)
    {
        $this->pass = $pass;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return $this->pass;
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}