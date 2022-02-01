<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;
use Snicco\Component\Templating\View\View;

class ToUppercaseComponent extends BladeComponent
{

    public function render(): View
    {
        return $this->view('uppercase');
    }

    public function toUpper($string): string
    {
        return strtoupper($string);
    }

}