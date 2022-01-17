<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Illuminate\View\Component as IlluminateComponent;

/**
 * @api
 */
abstract class BladeComponent extends IlluminateComponent
{
    
    /** @var BladeViewFactory */
    private $engine;
    
    public function setEngine(BladeViewFactory $engine)
    {
        $this->engine = $engine;
    }
    
    protected function view(string $view)
    {
        $view = str_replace('components.', '', $view);
        
        return $this->engine->make('components.'.$view);
    }
    
}