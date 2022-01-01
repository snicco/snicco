<?php

declare(strict_types=1);

namespace Snicco\Core\Traits;

use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\Internal\ConditionBlueprint;

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
            $attributes['url'],
            $attributes['controller'],
            $attributes['name'],
            $attributes['methods'],
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
        if ($this->isSerializedClosure($this->getController())) {
            $action = \Opis\Closure\unserialize($this->getController());
            
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