<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

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