<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

final class AlertAttributes extends BladeComponent
{
    public string $type;

    public string $message;

    public function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    public function render(): string
    {
        return $this->componentName('components.alert-attributes');
    }
}
