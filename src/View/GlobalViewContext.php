<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Support\Repository;

/**
 * @api
 */
class GlobalViewContext
{
    
    /**
     * @var array
     */
    private $context = [];
    
    public function add(string $name, $context)
    {
        if (is_array($context)) {
            $context = new Repository($context);
        }
        
        $this->context[$name] = $context;
    }
    
    public function get() :array
    {
        return array_map(function ($context) {
            return is_callable($context)
                ? call_user_func($context)
                : $context;
        }, $this->context);
    }
    
}