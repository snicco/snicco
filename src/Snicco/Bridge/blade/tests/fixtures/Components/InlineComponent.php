<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

final class InlineComponent extends BladeComponent
{
    public string $content;

    public function __construct(string $content)
    {
        $this->content = strtoupper($content);
    }

    public function render(): string
    {
        return <<<'CODE_SAMPLE'
Content:{{$content}},SLOT:{{ $slot }}
CODE_SAMPLE;
    }
}
