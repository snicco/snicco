<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Http\ResponseEmitter;
use Snicco\Testing\TestResponseEmitter;

trait InteractsWithContainer
{
    
    private function replaceBindings()
    {
        $this->swap(ResponseEmitter::class, new TestResponseEmitter());
    }
    
    /**
     * Swap an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     *
     * @return mixed
     */
    protected function swap(string $abstract, $instance)
    {
        return $this->instance($abstract, $instance);
    }
    
    /**
     * Register an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     *
     * @return mixed
     */
    protected function instance(string $abstract, $instance)
    {
        
        $this->app->container()->instance($abstract, $instance);
        
        return $instance;
    }
    
}