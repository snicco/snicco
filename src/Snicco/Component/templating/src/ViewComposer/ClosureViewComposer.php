<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Snicco\Component\Templating\View\View;

/**
 * @psalm-internal Snicco\Component\Templating
 */
final class ClosureViewComposer implements ViewComposer
{

    /**
     * @var Closure(View):View $composer
     */
    private Closure $composer;

    /**
     * @param Closure(View):View $composer
     */
    public function __construct(Closure $composer)
    {
        $this->composer = $composer;
    }

    public function compose(View $view): View
    {
        return ($this->composer)($view);
    }

}