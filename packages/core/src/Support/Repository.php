<?php

/*
 * Slightly modified version of the Illuminate/Config class.
 *
 * License: The MIT License (MIT) https://github.com/illuminate/config/blob/v8.35.1/LICENSE.md
 * Copyright (c) Taylor Otwell
 */

declare(strict_types=1);

namespace Snicco\Support;

use ArrayAccess;

class Repository implements ArrayAccess
{
    
    /**
     * @var array
     */
    protected $items = [];
    
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    public function has(string $key) :bool
    {
        return Arr::has($this->items, $key);
    }
    
    /**
     * Get the specified configuration value.
     *
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
    
    public function add(array $items)
    {
        $this->set($items);
    }
    
    /**
     * Get many configuration values.
     *
     * @param  string[]  $keys
     *
     * @return array
     */
    public function getMany($keys) :array
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
    
    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function prepend(string $key, $value)
    {
        $array = $this->get($key);
        
        array_unshift($array, $value);
        
        $this->set($key, $array);
    }
    
    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function push(string $key, $value) :void
    {
        $array = $this->get($key);
        
        $array[] = $value;
        
        $this->set($key, $array);
    }
    
    public function all() :array
    {
        return $this->items;
    }
    
    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    
    /**
     * Get a configuration option.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    
    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }
    
    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key);
    }
    
}
