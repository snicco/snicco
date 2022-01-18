<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Snicco\Component\Templating\View\View;

/**
 * @interal
 */
final class ClosureViewComposer implements ViewComposer
{
    
    private Closure $composer;
    
    public function __construct(Closure $composer)
    {
        $this->composer = $composer;
    }
    
    public function compose(View $view) :void
    {
        call_user_func($this->composer, $view);
    }
    
}