<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Arr;

class ConditionBucket
{
    
    private array $conditions;
    
    public function __construct(array $conditions = [])
    {
        
        $this->conditions = $conditions;
    }
    
    public static function createEmpty() :ConditionBucket
    {
        
        return new static([]);
        
    }
    
    public function add($condition)
    {
        
        $this->conditions[] = Arr::wrap($condition);
        
    }
    
    public function combine(ConditionBucket $old_conditions) :ConditionBucket
    {
        
        $this->mergePrevious($old_conditions->all());
        
        return $this;
        
    }
    
    public function all() :array
    {
        
        return $this->conditions;
        
    }
    
    private function mergePrevious(array $previous_group_conditions)
    {
        
        $this->conditions = array_merge($previous_group_conditions, $this->conditions);
        
    }
    
}