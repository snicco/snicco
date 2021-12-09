<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Core\Http\ResponseEmitter;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Testing\TestResponseEmitter;

trait InteractsWithContainer
{
    
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
        unset($this->app->container()[$abstract]);
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
    
    private function replaceBindings()
    {
        $this->app->container()->singleton(ResponseEmitter::class, function () {
            return new TestResponseEmitter($this->app->resolve(ResponsePreparation::class));
        });
    }
    
}