<?php

declare(strict_types=1);

namespace Snicco\Core\Configuration;

use Closure;
use ArrayAccess;
use Snicco\StrArr\Arr;
use Snicco\Core\Utils\Repository;

/**
 * @api
 */
final class WritableConfig implements ArrayAccess
{
    
    private Repository $repository;
    
    public function __construct(?Repository $repository = null)
    {
        $this->repository = $repository ?? new Repository();
    }
    
    public static function fromArray(array $items) :self
    {
        return new self(new Repository($items));
    }
    
    public function extend(string $key, $extend_with) :void
    {
        $existing_config = $this->repository->get($key, []);
        
        $value = $this->replace($extend_with, $existing_config);
        
        $this->repository->set($key, $value);
    }
    
    public function get(string $key, $default = null)
    {
        return $this->repository->get($key, $default);
    }
    
    public function set(string $key, $value) :void
    {
        $this->repository->set($key, $value);
    }
    
    public function extendIfEmpty(string $key, Closure $default)
    {
        $user_config = $this->repository->get($key, []);
        
        if ($user_config !== false && empty(str_replace(' ', '', $user_config))) {
            $this->repository->set($key, $default($this));
        }
    }
    
    public function toArray() :array
    {
        return $this->repository->toArray();
    }
    
    public function offsetExists($offset) :bool
    {
        return $this->repository->offsetExists($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->repository->offsetGet($offset);
    }
    
    public function offsetSet($offset, $value) :void
    {
        $this->repository->offsetSet($offset, $value);
    }
    
    public function offsetUnset($offset) :void
    {
        $this->repository->offsetUnset($offset);
    }
    
    public function has(string $key) :bool
    {
        return $this->offsetExists($key);
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
    private function _replace($app_config, $user_config)
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
    
    private function replace($extend_with, $exiting_config)
    {
        if ($this->isEmptyArray($exiting_config) && ! $this->isEmptyArray($extend_with)) {
            return $extend_with;
        }
        
        if ( ! is_array($extend_with)) {
            return $exiting_config;
        }
        
        $app_config_is_indexed = array_keys($extend_with) === range(0, count($extend_with) - 1);
        $user_config_is_indexed =
            array_keys($exiting_config) === range(0, count($exiting_config) - 1);
        
        if ($app_config_is_indexed && $user_config_is_indexed) {
            return Arr::combineNumerical($exiting_config, $extend_with);
        }
        
        $result = $exiting_config;
        
        foreach ($extend_with as $key => $app_value) {
            $result[$key] = $this->replace($app_value, Arr::get($exiting_config, $key, []));
        }
        
        return $result;
    }
    
    private function isEmptyArray($value) :bool
    {
        return [] === $value;
    }
    
}