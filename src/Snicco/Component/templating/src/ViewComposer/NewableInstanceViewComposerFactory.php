<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Snicco\Component\Templating\Exception\BadViewComposer;
use Throwable;

/**
 * @api Simple factory that tries to instantiate a class if a string is passed.
 */
final class NewableInstanceViewComposerFactory implements ViewComposerFactory
{

    /**
     * @param class-string<ViewComposer>|Closure $composer
     *
     * @throws BadViewComposer
     */
    public function create($composer): ViewComposer
    {
        if ($composer instanceof Closure) {
            return new ClosureViewComposer($composer);
        }

        if (is_string($composer) && class_exists($composer)) {
            try {
                return new $composer;
            } catch (Throwable $e) {
                throw new BadViewComposer(
                    "The view composer class [$composer] is not a newable.",
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        throw new BadViewComposer('A view composer has to be a class name or a closure.');
    }

}