<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Closure;
use Opis\Closure\SerializableClosure;
use Snicco\Routing\ConditionBlueprint;

trait ExportsRoute
{
    
    /**
     * Get a representation of the route as an array.
     *
     * @return array
     */
    public function asArray() :array
    {
        $route = [
            'action' => $this->action,
            'name' => $this->name ?? '',
            'middleware' => $this->middleware ?? [],
            'condition_blueprints' => $this->condition_blueprints,
            'namespace' => $this->namespace ?? '',
            'defaults' => $this->defaults ?? [],
            'url' => $this->url,
            'wp_query_filter' => $this->wp_query_filter,
            'regex' => $this->regex,
            'segment_names' => $this->segment_names,
            'trailing_slash' => $this->trailing_slash,
            'methods' => $this->methods,
            'is_fallback' => $this->is_fallback,
        ];
        
        return $this->prepareForVarExport($route);
    }
    
    private function prepareForVarExport(array $asArray) :array
    {
        $asArray['action'] = $this->serializeAttribute($asArray['action']);
        
        $asArray['wp_query_filter'] = $this->serializeAttribute($asArray['wp_query_filter']);
        
        $asArray['condition_blueprints'] = array_map(function (ConditionBlueprint $condition) {
            return $this->serializeCustomConditions($condition);
        }, $asArray['condition_blueprints']);
        
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