<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewComposer;

use Closure;
use Throwable;
use Snicco\Component\Templating\Exception\BadViewComposer;

/**
 * @api Simple factory that tries to instantiate a class if a string is passed.
 */
final class NewableInstanceViewComposerFactory implements ViewComposerFactory
{
    
    /**
     * @param  string|Closure  $composer
     */
    public function create($composer) :ViewComposer
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
                    $e->getCode(),
                    $e
                );
            }
        }
        
        throw new BadViewComposer("A view composer has to be a class name or a closure.");
    }
    
}