<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Closure;
use Opis\Closure\SerializableClosure;
use Snicco\Routing\ConditionBlueprint;

trait PreparesRouteForExport
{
    
    private function prepareForVarExport(array $asArray) :array
    {
        
        $asArray['action'] = $this->serializeAttribute($asArray['action']);
        
        $asArray['wp_query_filter'] = $this->serializeAttribute($asArray['wp_query_filter']);
        
        $asArray['conditions'] = collect($asArray['conditions'])
            ->map(function (ConditionBlueprint $condition) {
                
                return $this->serializeCustomConditions($condition);
                
            })->all();
        
        return $asArray;
        
    }
    
    private function serializeAttribute($action)
    {
        
        if ($action instanceof Closure) {
            
            $closure = new SerializableClosure($action);
            
            $action = \Opis\Closure\serialize($closure);
            
        }
        
        if (is_object($action)) {
            
            $action = \Opis\Closure\serialize($action);
            
        }
        
        return $action;
        
    }
    
    private function serializeCustomConditions(ConditionBlueprint $condition_blueprint) :array
    {
        
        $as_array = $condition_blueprint->asArray();
        
        if (is_object($as_array['instance'])) {
            
            $as_array['instance'] = $this->serializeAttribute($as_array['instance']);
            
        }
        
        return $as_array;
        
    }
    
}