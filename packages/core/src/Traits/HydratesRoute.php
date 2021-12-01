<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Routing\Route;
use Snicco\Routing\ConditionBlueprint;

trait HydratesRoute
{
    
    /**
     * Reconstruct a route from an array of attributes.
     *
     * @param  array  $attributes
     *
     * @return Route
     */
    public static function hydrate(array $attributes) :Route
    {
        $route = new Route(
            $attributes['methods'],
            $attributes['url'],
            $attributes['action']
        );
        
        foreach (Arr::except($attributes, ['url', 'methods', 'action']) as $attribute => $value) {
            $route->{$attribute} = $value;
        }
        
        $route->unserializeAction();
        $route->unserializeAction();
        $route->unserializeWpQueryFilter();
        $route->unserializeConditionBlueprints();
        
        return $route;
    }
    
    private function unserializeAction()
    {
        if ($this->isSerializedClosure($this->getAction())) {
            $action = \Opis\Closure\unserialize($this->getAction());
            
            $this->handle($action);
        }
    }
    
    private function isSerializedClosure($action) :bool
    {
        return is_string($action)
               && Str::startsWith($action, 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
    }
    
    private function unserializeWpQueryFilter()
    {
        if ($this->isSerializedClosure($this->wp_query_filter)) {
            $query_filter = \Opis\Closure\unserialize($this->wp_query_filter);
            
            $this->wp_query_filter = $query_filter;
        }
    }
    
    private function unserializeConditionBlueprints()
    {
        $this->condition_blueprints = array_map(function ($blueprint) {
            if (is_string($blueprint['instance']) && function_exists('\Opis\Closure\unserialize')) {
                $blueprint['instance'] = \Opis\Closure\unserialize($blueprint['instance']);
            }
            
            return ConditionBlueprint::hydrate($blueprint);
        }, $this->condition_blueprints);
    }
    
}