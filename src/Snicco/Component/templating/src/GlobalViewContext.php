<?php

declare(strict_types=1);

namespace Snicco\Component\Templating;

use Closure;
use Snicco\Component\ParameterBag\ParameterPag;

/**
 * @api
 */
final class GlobalViewContext
{
    
    /**
     * @var array<string,mixed>
     */
    private array $context = [];
    
    public function add(string $name, $context)
    {
        if (is_array($context)) {
            $context = new ParameterPag($context);
        }
        
        $this->context[$name] = $context;
    }
    
    /**
     * @interal
     */
    public function get() :array
    {
        return array_map(function ($context) {
            return ($context instanceof Closure)
                ? call_user_func($context)
                : $context;
        }, $this->context);
    }
    
}