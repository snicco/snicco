<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use Closure;
use Throwable;
use Snicco\View\ClosureViewComposer;
use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewComposerFactory;
use Snicco\View\Exceptions\BadViewComposerException;

/**
 * @internal
 */
final class NewableInstanceViewComposerFactory implements ViewComposerFactory
{
    
    public function create($composer) :ViewComposer
    {
        if ($composer instanceof Closure) {
            return new ClosureViewComposer($composer);
        }
        
        if (is_string($composer) && class_exists($composer)) {
            try {
                return new $composer;
            } catch (Throwable $e) {
                throw new BadViewComposerException(
                    "The view composer class [$composer] is not a newable.",
                    $e->getCode(),
                    $e
                );
            }
        }
        
        throw new BadViewComposerException("A view composer has to be a class name or a closure.");
    }
    
}