<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Http\Psr7;

use stdClass;
use Snicco\StrArr\Arr;
use Snicco\StrArr\Str;

trait InteractsWithInput
{
    
    final public function all() :array
    {
        return $this->inputSource();
    }
    
    final public function server(string $key, $default = null)
    {
        return Arr::get($this->getServerParams(), $key, $default);
    }
    
    final public function query(string $key = null, $default = null)
    {
        $query = $this->getQueryParams();
        
        if ( ! $key) {
            return $query;
        }
        
        return Arr::get($query, $key, $default);
    }
    
    final public function queryString() :string
    {
        $qs = $this->getUri()->getQuery();
        
        while (Str::endsWith($qs, '&') || Str::endsWith($qs, '=')) {
            $qs = mb_substr($qs, 0, -1);
        }
        
        return $qs;
    }
    
    final public function body(string $name = null, $default = null)
    {
        return $this->post($name, $default);
    }
    
    final public function post(string $name = null, $default = null)
    {
        if ( ! $name) {
            return $this->getParsedBody() ?? [];
        }
        
        return Arr::get($this->getParsedBody(), $name, $default);
    }
    
    final public function boolean($key = null, $default = false)
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * This method supports "*" as wildcards in the key.
     */
    final public function input($key = null, $default = null)
    {
        $all = $this->all();
        
        if (null === $key) {
            return $all;
        }
        
        return Arr::dataGet($all, $key, $default);
    }
    
    /**
     * This method does not support * WILDCARDS
     */
    final public function only($keys) :array
    {
        $results = [];
        
        $input = $this->all();
        
        $placeholder = new stdClass;
        
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = Arr::dataGet($input, $key, $placeholder);
            
            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }
        
        return $results;
    }
    
    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * @param  string|string[]  $keys
     */
    final public function filled($keys) :bool
    {
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * This method does not support * WILDCARDS
     */
    final public function except($keys) :array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        $results = $this->all();
        
        Arr::forget($results, $keys);
        
        return $results;
    }
    
    final public function hasAny($keys) :bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        $input = $this->all();
        
        return Arr::hasAny($input, $keys);
    }
    
    /**
     * Will return falls if any of the provided keys is missing.
     */
    final public function missing($key) :bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        return ! $this->has($keys);
    }
    
    final public function has($key) :bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        
        $input = $this->all();
        
        foreach ($keys as $value) {
            if ( ! Arr::has($input, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function inputSource() :array
    {
        $input = in_array($this->realMethod(), ['GET', 'HEAD'])
            ? $this->getQueryParams()
            : $this->getParsedBody();
        
        return (array) $input;
    }
    
    private function isEmptyString(string $key) :bool
    {
        $value = $this->input($key);
        
        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }
    
}