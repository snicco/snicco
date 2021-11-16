<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Snicco\Contracts\ViewEngineInterface;
use Illuminate\View\Component as IlluminateComponent;

abstract class BladeComponent extends IlluminateComponent
{
    
    protected ViewEngineInterface $engine;
    
    public function setEngine(BladeEngine $engine)
    {
        $this->engine = $engine;
    }
    
    protected function view(string $view)
    {
        $view = str_replace('components.', '', $view);
        
        return $this->engine->make('components.'.$view);
    }
    
}