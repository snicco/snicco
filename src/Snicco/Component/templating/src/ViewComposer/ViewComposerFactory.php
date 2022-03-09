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
     * @return T
     *
     * @throws BadViewComposer
     */
    public function create(string $composer): ViewComposer;
}
