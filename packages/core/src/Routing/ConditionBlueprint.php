<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Str;
use InvalidArgumentException;
use Snicco\Contracts\Condition;
use Snicco\Routing\Conditions\CustomCondition;

class ConditionBlueprint
{
    
    const NEGATES_SIGN = '!';
    const NEGATES_WORD = 'negate';
    
    protected          $type;
    protected          $args;
    private ?Condition $instance;
    
    /** @var null|string|Condition */
    private $negates = null;
    
    public function __construct($condition = null, array $arguments = [])
    {
        if (is_null($condition)) {
            return;
        }
        
        [$type, $args] = $this->parseTypeAndArgs($condition, $arguments);
        
        $this->type = $type;
        $this->args = $args;
        
        $this->instance = $this->parseInstance($condition, $arguments);
    }
    
    public static function hydrate(array $attributes) :ConditionBlueprint
    {
        $blueprint = new ConditionBlueprint();
        
        $blueprint->type = $attributes['type'];
        $blueprint->args = $attributes['args'];
        $blueprint->instance = $attributes['instance'];
        $blueprint->negates = $attributes['negates'];
        
        return $blueprint;
    }
    
    public function args() :array
    {
        return $this->args;
    }
    
    public function type() :string
    {
        return $this->type;
    }
    
    public function instance() :?object
    {
        return $this->instance;
    }
    
    public function negates() :?string
    {
        return $this->negates;
    }
    
    public function asArray() :array
    {
        return [
            
            'type' => $this->type,
            'args' => $this->args,
            'instance' => $this->instance,
            'negates' => $this->negates,
        
        ];
    }
    
    private function parseTypeAndArgs($condition, array $args) :array
    {
        if (is_callable($condition)) {
            return [CustomCondition::class, $args];
        }
        
        if ($condition === self::NEGATES_WORD) {
            $copy = $args;
            
            [$type, $args] = $this->parseTypeAndArgs(array_shift($copy), $copy);
            
            $this->negates = $type;
            
            return [self::NEGATES_WORD, $args];
        }
        
        if (is_string($condition)) {
            if (Str::startsWith($condition, self::NEGATES_SIGN)) {
                $this->negates = Str::after($condition, self::NEGATES_SIGN);
                
                $type = self::NEGATES_WORD;
                
                return [$type, $args];
            }
            
            return [$condition, $args];
        }
        
        if ($condition instanceof Condition) {
            return [get_class($condition), $args];
        }
        
        throw new InvalidArgumentException("An invalid condition was provided for a route.");
    }
    
    private function parseInstance($condition, $arguments = []) :?Condition
    {
        if ($this->type === self::NEGATES_WORD && is_object($arguments[0] ?? '')) {
            return $this->parseInstance($arguments[0]);
        }
        
        if (is_callable($condition)) {
            return new CustomCondition($condition, $this->args);
        }
        
        return ($condition instanceof Condition) ? $condition : null;
    }
    
}
