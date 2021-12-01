<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Snicco\Contracts\Condition;
use Snicco\Shared\ContainerAdapter;
use Snicco\Routing\ConditionBlueprint;
use Snicco\Support\ReflectionDependencies;
use Snicco\Routing\Conditions\NegateCondition;
use Snicco\Routing\Conditions\CustomCondition;

class RouteConditionFactory
{
    
    /**
     * Registered condition types.
     *
     * @var array<string, string>
     */
    private array            $condition_types;
    private ContainerAdapter $container;
    
    public function __construct(array $condition_types, ContainerAdapter $container)
    {
        $this->condition_types = $condition_types;
        $this->container = $container;
    }
    
    /**
     * @param  ConditionBlueprint[]  $condition_blueprints
     *
     * @return array
     */
    public function buildConditions(array $condition_blueprints) :array
    {
        $conditions = array_map(function (ConditionBlueprint $condition) {
            if ($compiled = $this->alreadyCompiled($condition)) {
                return $compiled;
            }
            
            return $this->new($condition);
        }, $condition_blueprints);
        
        return array_unique($conditions, SORT_REGULAR);
    }
    
    private function alreadyCompiled(ConditionBlueprint $condition) :?object
    {
        if ($condition->type() === ConditionBlueprint::NEGATES_WORD) {
            return null;
        }
        
        return $condition->instance();
    }
    
    private function new(ConditionBlueprint $blueprint)
    {
        $type = $this->transformAliasToClassName($blueprint->type());
        $conditions_arguments = $blueprint->args();
        
        if ($type === $this->condition_types[ConditionBlueprint::NEGATES_WORD]) {
            return $this->newNegated($blueprint, $conditions_arguments);
        }
        
        if ($blueprint->instance()) {
            return $blueprint->instance();
        }
        
        $deps = (new ReflectionDependencies($this->container))->build($type, $conditions_arguments);
        
        return new $type(...$deps);
    }
    
    private function transformAliasToClassName(string $type)
    {
        return $this->condition_types[$type] ?? $type;
    }
    
    private function newNegated(ConditionBlueprint $blueprint, array $args) :NegateCondition
    {
        $instance = $blueprint->instance();
        
        if ($instance instanceof Condition) {
            return new NegateCondition($instance);
        }
        
        if (is_callable($instance)) {
            return new NegateCondition(new CustomCondition($instance, $args));
        }
        
        if (is_callable($negates = $blueprint->negates())) {
            return new NegateCondition(new CustomCondition($negates, $args));
        }
        
        $type = $this->transformAliasToClassName($negates);
        $deps = (new ReflectionDependencies($this->container))->build($type, $args);
        
        return new NegateCondition(new $type(...$deps));
    }
    
}
