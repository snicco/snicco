<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Illuminate\View\View as IllumianteView;
use RuntimeException;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\ValueObject\FilePath;
use Snicco\Component\Templating\ValueObject\View;

final class BladeViewComposer
{
    private ViewContextResolver $composers;

    public function __construct(ViewContextResolver $composers)
    {
        $this->composers = $composers;
    }

    public function handleEvent(string $event_name, array $payload): void
    {
        if (! isset($payload[0]) || ! $payload[0] instanceof IllumianteView) {
            throw new RuntimeException(sprintf('Expected payload[0] to be instance of [%s].', IllumianteView::class, ));
        }

        $illuminate_view = $payload[0];

        /** @var array<string,mixed> $data */
        $data = $illuminate_view->getData();

        $snicco_view = new View(
            $illuminate_view->name(),
            FilePath::fromString($illuminate_view->getPath()),
            BladeViewFactory::class,
            $data
        );

        $snicco_view = $this->composers->compose($snicco_view);

        $illuminate_view->with($snicco_view->context());
    }
}
