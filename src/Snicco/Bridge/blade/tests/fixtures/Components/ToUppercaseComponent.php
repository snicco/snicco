<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

final class ToUppercaseComponent extends BladeComponent
{
    public function render(): string
    {
        return $this->view('uppercase');
    }

    public function toUpper(string $string): string
    {
        return strtoupper($string);
    }
}
