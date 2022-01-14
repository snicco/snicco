<?php

declare(strict_types=1);

/*
 * Class greatly inspired and in most parts copied from Laravel`s ViewErrorBag
 * @see https://github.com/laravel/framework/blob/v8.76.1/src/Illuminate/Support/ViewErrorBag.php
 * License: The MIT License (MIT) https://github.com/illuminate/support/blob/master/LICENSE.md
 * Copyright (c) Taylor Otwell
 */

namespace Snicco\Session;

use Snicco\StrArr\Arr;

/**
 * @api
 * @mixin MessageBag
 */
final class SessionErrors
{
    
    private $bags = [];
    
    public function hasBag(string $key = 'default') :bool
    {
        return isset($this->bags[$key]);
    }
    
    public function getBag(string $key) :MessageBag
    {
        return Arr::get($this->bags, $key) ? : new MessageBag();
    }
    
    /**
     * Add a new MessageBag instance to the bags.
     */
    public function put(string $key, MessageBag $bag) :void
    {
        $this->bags[$key] = $bag;
    }
    
    /**
     * Determine if the default message bag has any messages.
     */
    public function any() :bool
    {
        return $this->count() > 0;
    }
    
    /**
     * Get the number of messages in the default bag.
     */
    public function count() :int
    {
        return $this->getBag('default')->count();
    }
    
    /**
     * Dynamically call methods on the default bag.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->getBag('default')->$method(...$parameters);
    }
    
    public function __toString() :string
    {
        return (string) $this->getBag('default');
    }
    
    public function __clone()
    {
        $bags = [];
        
        foreach ($this->bags as $key => $bag) {
            $bags[$key] = clone $bag;
        }
        $this->bags = $bags;
    }
    
}