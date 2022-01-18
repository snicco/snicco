<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

class HelloWorld extends BladeComponent
{
    
    public function render()
    {
        return $this->view('components.hello-world');
    }
    
}