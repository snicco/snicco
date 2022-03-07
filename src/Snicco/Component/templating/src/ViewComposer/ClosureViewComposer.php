<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Snicco\Component\Templating\View\View;

/**
 * @template T of View
 * @psalm-internal Snicco\Component\Templating
 */
final class ClosureViewComposer implements ViewComposer
{

    /**
     * @var Closure(T):T $composer
     */
    private Closure $composer;

    /**
     * @param Closure(T):T $composer
     */
    public function __construct(Closure $composer)
    {
        $this->composer = $composer;
    }

    /**
     * Add context values to the passed view
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidArgument
     */
    public function compose(View $view): View
    {
        return ($this->composer)($view);
    }

}