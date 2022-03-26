<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Context;

use Snicco\Component\Templating\Exception\CantCreateViewComposer;

interface ViewComposerFactory
{
    /**
     * @template T of ViewComposer
     *
     * @param class-string<T> $composer
     *
     * @throws CantCreateViewComposer
     *
     * @return T
     */
    public function create(string $composer): ViewComposer;
}
