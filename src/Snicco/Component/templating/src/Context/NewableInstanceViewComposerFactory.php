<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Context;

use Snicco\Component\Templating\Exception\CantCreateViewComposer;
use Throwable;

final class NewableInstanceViewComposerFactory implements ViewComposerFactory
{
    public function create(string $composer): ViewComposer
    {
        try {
            return new $composer();
        } catch (Throwable $e) {
            throw new CantCreateViewComposer(
                sprintf('The view composer class [%s] is not a newable.', $composer),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
