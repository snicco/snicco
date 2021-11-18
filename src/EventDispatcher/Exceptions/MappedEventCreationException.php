<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Exceptions;

use Throwable;
use RuntimeException;

/**
 * @api
 */
final class MappedEventCreationException extends RuntimeException
{
    
    /**
     * @param  array  $wordpress_hook_arguments
     * @param  string  $event_class
     * @param  Throwable  $previous
     *
     * @return static
     */
    public static function becauseTheEventCouldNotBeConstructorWithArgs(array $wordpress_hook_arguments, string $event_class, Throwable $previous) :self
    {
        $args = json_encode($wordpress_hook_arguments);
        
        $message =
            "The mapped event [$event_class] could not be instantiated with the passed received arguments from WordPress.";
        if ($args !== false) {
            $message .= " Received [$args]";
        }
        
        return new MappedEventCreationException($message, $previous->getCode(), $previous);
    }
    
}