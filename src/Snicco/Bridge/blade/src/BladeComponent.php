<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Illuminate\View\Component as IlluminateComponent;

abstract class BladeComponent extends IlluminateComponent
{
    /**
     * This method must either return a view name or a view contents as a string.
     */
    abstract public function render(): string;

    protected function view(string $view): string
    {
        $view = str_replace('components.', '', $view);

        return "components.$view";
    }
}
