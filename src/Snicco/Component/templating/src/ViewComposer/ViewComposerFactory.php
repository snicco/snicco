<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Snicco\Component\Templating\Exception\BadViewComposer;

interface ViewComposerFactory
{
    /**
     * @template T of ViewComposer
     *
     * @param class-string<T> $composer
     *
     * @throws BadViewComposer
     *
     * @return T
     */
    public function create(string $composer): ViewComposer;
}
