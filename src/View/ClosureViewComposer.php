<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;
use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewInterface;

/**
 * @internal
 */
class ClosureViewComposer implements ViewComposer
{
    
    /**
     * @var Closure
     */
    private $composer;
    
    public function __construct(Closure $composer)
    {
        $this->composer = $composer;
    }
    
    public function compose(ViewInterface $view) :void
    {
        call_user_func($this->composer, $view);
    }
    
}