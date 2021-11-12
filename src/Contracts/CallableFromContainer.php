<?php

declare(strict_types=1);

namespace Snicco\Contracts;

/**
 * The most generic type of object that can be resolved from the service container.
 *
 * @interal
 */
interface CallableFromContainer
{
    
    /**
     * @param  array  $args
     *
     * @return mixed
     */
    public function executeUsing(array $args);
    
}