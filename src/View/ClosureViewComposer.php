<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;
use Snicco\Contracts\ViewComposer;
use Snicco\Contracts\ViewInterface;

class ClosureViewComposer implements ViewComposer
{
    
    private Closure $composer;
    
    public function __construct(Closure $composer)
    {
        $this->composer = $composer;
    }
    
    public function compose(ViewInterface $view)
    {
        call_user_func($this->composer, $view);
    }
    
}