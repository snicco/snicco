<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Snicco\Component\Templating\Exception\BadViewComposer;
use Throwable;

final class NewableInstanceViewComposerFactory implements ViewComposerFactory
{
    public function create(string $composer): ViewComposer
    {
        try {
            return new $composer();
        } catch (Throwable $e) {
            throw new BadViewComposer(
                "The view composer class [$composer] is not a newable.",
                (int)$e->getCode(),
                $e
            );
        }
    }
}
