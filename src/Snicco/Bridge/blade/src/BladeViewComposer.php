<?php

declare(strict_types=1);


namespace Snicco\Bridge\Blade;

use Illuminate\View\View;
use RuntimeException;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;

final class BladeViewComposer
{
    private ViewComposerCollection $composers;

    public function __construct(ViewComposerCollection $composers)
    {
        $this->composers = $composers;
    }

    public function handleEvent(string $view_name, array $payload): void
    {
        if (! isset($payload[0]) || ! $payload[0] instanceof View) {
            throw new RuntimeException(
                sprintf(
                    'Expected payload[0] to be instance of [%s].',
                    View::class,
                )
            );
        }
        $view = $payload[0];
        $blade_view = $this->composers->compose(new BladeView($view));
        $view->with($blade_view->context());
    }
}
