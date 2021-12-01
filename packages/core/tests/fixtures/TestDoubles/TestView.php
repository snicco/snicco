<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\TestDoubles;

use Snicco\Support\Arr;
use Snicco\View\Implementations\PHPView;
use Snicco\View\Contracts\ViewInterface;

class TestView implements ViewInterface
{
    
    private array  $context = [];
    private string $name;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function with($key, $value = null) :ViewInterface
    {
        if (is_array($key)) {
            $this->context = array_merge($this->context(), $key);
        }
        else {
            $this->context[$key] = $value;
        }
        
        return $this;
    }
    
    public function context(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->context;
        }
        
        return Arr::get($this->context, $key, $default);
    }
    
    public function toResponsable()
    {
        return $this->toString();
    }
    
    public function toString() :string
    {
        $context = '[';
        
        foreach ($this->context as $key => $value) {
            if ($key === '__view') {
                continue;
            }
            $context .= $key.'=>'.$value.',';
        }
        $context = rtrim($context, ',');
        $context .= ']';
        
        return 'VIEW:'.$this->name.',CONTEXT:'.$context;
    }
    
    public function path() :string
    {
    }
    
    public function parent() :?PHPView
    {
    }
    
    public function name() :string
    {
        return $this->name;
    }
    
}