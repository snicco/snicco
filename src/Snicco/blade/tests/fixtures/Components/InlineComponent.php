<?php

declare(strict_types=1);

namespace Tests\Blade\fixtures\Components;

use Snicco\Blade\BladeComponent;

class InlineComponent extends BladeComponent
{
    
    public $content;
    
    public function __construct($content)
    {
        $this->content = strtoupper($content);
    }
    
    public function render()
    {
        return <<<'blade'
Content:{{$content}},SLOT:{{ $slot }}
blade;
    }
    
}