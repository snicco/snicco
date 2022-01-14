<?php

/*
 * Modified version of the Illuminate/Config class with strict type hinting and final attribute.
 *
 * License: The MIT License (MIT) https://github.com/illuminate/config/blob/v8.35.1/LICENSE.md
 * Copyright (c) Taylor Otwell
 */

declare(strict_types=1);

namespace Snicco\Support;

use ArrayAccess;

final class Repository implements ArrayAccess
{
    
    private array $items;
    
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    public function has(string $key) :bool
    {
        return Arr::has($this->items, $key);
    }
    
    /**
     * @param  array|string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }
        
        return Arr::get($this->items, $key, $default);
    }
    
    public function add(array $items) :void
    {
        $this->set($items);
    }
    
    public function getMany(array $keys) :array
    {
        $config = [];
        
        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }
            
            $config[$key] = Arr::get($this->items, $key, $default);
        }
        
        return $config;
    }
    
    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     */
    public function set($key, $value = null) :void
    {
        $keys = is_array($key) ? $key : [$key => $value];
        
        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }
    
    public function prepend(string $key, $value) :void
    {
        $array = $this->get($key);
        
        array_unshift($array, $value);
        
        $this->set($key, $array);
    }
    
    public function push(string $key, $value) :void
    {
        $array = $this->get($key);
        
        $array[] = $value;
        
        $this->set($key, $array);
    }
    
    public function toArray() :array
    {
        return $this->items;
    }
    
    /**
     * @param  string  $offset
     */
    public function offsetExists($offset) :bool
    {
        return $this->has($offset);
    }
    
    /**
     * @param  string  $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    /**
     * @param  string  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value) :void
    {
        $this->set($offset, $value);
    }
    
    /**
     * @param  string  $offset
     */
    public function offsetUnset($offset) :void
    {
        $this->set($offset);
    }
    
}
