<?php

declare(strict_types=1);

namespace Tests\Blade\fixtures\Components;

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