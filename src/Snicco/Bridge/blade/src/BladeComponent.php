<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Illuminate\View\Component as IlluminateComponent;
use Snicco\Component\Templating\View\View;

/**
 * @api
 */
abstract class BladeComponent extends IlluminateComponent
{

    private BladeViewFactory $engine;

    public function setEngine(BladeViewFactory $engine): void
    {
        $this->engine = $engine;
    }

    protected function view(string $view): View
    {
        $view = str_replace('components.', '', $view);

        return $this->engine->make('components.' . $view);
    }

}