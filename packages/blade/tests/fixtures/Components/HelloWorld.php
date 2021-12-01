<?php

declare(strict_types=1);

namespace Tests\Blade\fixtures\Components;

use Snicco\Blade\BladeComponent;

class HelloWorld extends BladeComponent
{
    
    public function render()
    {
        return $this->view('components.hello-world');
    }
    
}