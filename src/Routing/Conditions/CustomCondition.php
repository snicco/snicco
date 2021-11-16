<?php

declare(strict_types=1);

namespace Snicco\Routing\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;

class CustomCondition implements Condition
{
    
    /** @var callable|string */
    private $callable;
    
    private array $arguments;
    
    public function __construct(callable $callable, $args = [])
    {
        $this->callable = $callable;
        $this->arguments = $args;
    }
    
    public function getCallable()
    {
        return $this->callable;
    }
    
    public function setCallable($callable)
    {
        $this->callable = $callable;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        return call_user_func_array($this->callable, $this->arguments);
    }
    
    public function getArguments(Request $request) :array
    {
        return $this->arguments;
    }
    
}
