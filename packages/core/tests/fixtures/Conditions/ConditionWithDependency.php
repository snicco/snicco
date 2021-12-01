<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Conditions;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Condition;
use Tests\Codeception\shared\TestDependencies\Foo;

class ConditionWithDependency implements Condition
{
    
    private bool $make_it_pass;
    private Foo  $foo;
    
    public function __construct(Foo $foo, $make_it_pass)
    {
        $this->make_it_pass = $make_it_pass;
        $this->foo = $foo;
    }
    
    public function isSatisfied(Request $request) :bool
    {
        if ( ! isset($this->foo)) {
            return false;
        }
        
        return $this->make_it_pass === true || $this->make_it_pass === 'foobar';
    }
    
    public function getArguments(Request $request) :array
    {
        return [];
    }
    
}