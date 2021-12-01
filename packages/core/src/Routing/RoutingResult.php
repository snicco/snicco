<?php

declare(strict_types=1);

namespace Snicco\Routing;

class RoutingResult
{
    
    private ?Route $route;
    private array  $captured_segments;
    private array  $compiled_segments;
    
    public function __construct(?Route $route, array $captured_segments = [])
    {
        $this->route = $route;
        $this->captured_segments = $captured_segments;
    }
    
    public function route() :?Route
    {
        return $this->route;
    }
    
    /**
     * @return array<string,mixed>
     */
    public function capturedUrlSegmentValues() :array
    {
        if ( ! isset($this->compiled_segments)) {
            $this->compiled_segments = array_map(function ($value) {
                $value = ( ! is_int($value)) ? rawurldecode($value) : $value;
                
                if (is_numeric($value)) {
                    $value = intval($value);
                }
                return $value;
            }, $this->captured_segments);
        }
        
        return $this->compiled_segments;
    }
    
    public function hasRoute() :bool
    {
        return $this->route instanceof Route || is_array($this->route);
    }
    
}