<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Support\VariableBag;

class GlobalContext
{
    
    private array $context = [];
    
    public function add(string $name, $context)
    {
        
        if (is_array($context)) {
            
            $context = new VariableBag($context);
            
        }
        
        $this->context[$name] = $context;
    }
    
    public function get() :array
    {
        
        return $this->context;
    }
    
}