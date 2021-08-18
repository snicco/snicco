<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;
use Snicco\Traits\ReflectsCallable;
use Snicco\Contracts\ViewComposer as ViewComposerInterface;

class ViewComposer implements ViewComposerInterface
{
    
    use ReflectsCallable;
    
    /**
     * A closures that wraps the actual view composer
     * registered by the user.
     * All view composers are resolved from the
     * service container.
     */
    private Closure $executable_composer;
    
    public function __construct(Closure $executable_closure)
    {
        
        $this->executable_composer = $executable_closure;
        
    }
    
    public function executeUsing(...$args)
    {
        
        $closure = $this->executable_composer;
        
        $payload = $this->buildNamedParameters($this->unwrap($closure), $args);
        
        return $closure($payload);
        
    }
    
}