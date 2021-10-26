<?php

declare(strict_types=1);

namespace Snicco\Routing;

class RoutingResult
{
    
    /** @var Route|array|null */
    private $route;
    
    private array $payload;
    
    private array $compiled_segments;
    
    /**
     * @param  Route|array|null  $route
     * @param  array  $payload
     */
    public function __construct($route, array $payload = [])
    {
        
        $this->route = $route;
        $this->payload = $payload;
        
    }
    
    public function route()
    {
        
        return $this->route;
    }
    
    public function capturedUrlSegmentValues() :array
    {
        
        if ( ! isset($this->compiled_segments)) {
            $values = collect($this->payload)->map(function ($value) {
                
                $value = ( ! is_int($value)) ? rawurldecode($value) : $value;
                
                if (is_numeric($value)) {
                    $value = intval($value);
                }
                return $value;
                
            });
            
            $this->compiled_segments = $values->all();
        }
        
        return $this->compiled_segments;
        
    }
    
}