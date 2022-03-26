<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

final class HelloWorld extends BladeComponent
{
    public function render(): string
    {
        return $this->componentName('components.hello-world');
    }
}
