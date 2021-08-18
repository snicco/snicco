<?php

declare(strict_types=1);

namespace Tests\integration\Blade\Components;

use Snicco\Blade\BladeComponent;

class ToUppercaseComponent extends BladeComponent
{
    
    public function render()
    {
        return $this->view('uppercase');
    }
    
    public function toUpper($string)
    {
        
        return strtoupper($string);
        
    }
    
}