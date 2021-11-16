<?php

declare(strict_types=1);

namespace Snicco\Application;

use Closure;
use Snicco\Support\Arr;
use Snicco\Support\Repository;

class Config extends Repository
{
    
    public function remove(string $key)
    {
        Arr::forget($this->items, $key);
    }
    
    public function extend(string $key, $app_config) :void
    {
        $user_config = $this->get($key, []);
        
        $value = $this->replace($app_config, $user_config);
        
        $this->set($key, $value);
    }
    
    public function extendIfEmpty(string $key, Closure $default)
    {
        $user_config = $this->get($key, []);
        
        if ($user_config !== false && empty(str_replace(' ', '', $user_config))) {
            $this->set($key, $default($this));
        }
    }
    
    public function seedFromCache(array $items)
    {
        $this->items = $items;
    }
    
    /**
     * Recursively replace default values with the passed config.
     * - If either value is not an array, the config value will be used.
     * - If both are an indexed array, the config value will be used.
     * - If either is a keyed array, array_replace will be used with config having priority.
     *
     * @param  mixed  $app_config
     * @param  mixed  $user_config
     *
     * @return mixed
     */
    private function replace($app_config, $user_config)
    {
        if ($this->isEmptyArray($user_config) && ! $this->isEmptyArray($app_config)) {
            return $app_config;
        }
        
        if ( ! is_array($app_config)) {
            return $user_config;
        }
        
        $app_config_is_indexed = array_keys($app_config) === range(0, count($app_config) - 1);
        $user_config_is_indexed = array_keys($user_config) === range(0, count($user_config) - 1);
        
        if ($app_config_is_indexed && $user_config_is_indexed) {
            return Arr::combineNumerical($user_config, $app_config);
        }
        
        $result = $user_config;
        
        foreach ($app_config as $key => $app_value) {
            $result[$key] = $this->replace($app_value, Arr::get($user_config, $key, []));
        }
        
        return $result;
    }
    
    private function isEmptyArray($value) :bool
    {
        return $value === [];
    }
    
}