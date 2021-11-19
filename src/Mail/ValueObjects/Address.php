<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

use WP_User;
use stdClass;
use InvalidArgumentException;

/**
 * @internal
 */
final class Address
{
    
    public static function normalize($recipient, string $map_into) :Name
    {
        if ( ! is_subclass_of($map_into, $type = Name::class)) {
            throw new InvalidArgumentException(
                "Email values can only be mapped into an object of type [$type]."
            );
        }
        
        if (is_string($recipient)) {
            if ( ! self::isFormatted($recipient)) {
                return new $map_into($recipient);
            }
            // @see https://regexr.com/69uh5
            preg_match_all('/^(?<name>\w+\s?\w+)?\s*<(?<email>[^>]+)>$/', $recipient, $parts);
            return new $map_into($parts['email'][0] ??= '', $parts['name'][0] ??= '');
        }
        if ($recipient instanceof WP_User) {
            return call_user_func([$map_into, 'fromWPUser'], $recipient);
        }
        
        if (is_array($recipient)) {
            if ( ! isset($recipient['name']) || ! isset($recipient['email'])) {
                return new $map_into(...array_values($recipient));
            }
            
            return new $map_into($recipient['email'], $recipient['name']);
        }
        
        if ($recipient instanceof stdClass) {
            return new $map_into($recipient->email, $recipient->name);
        }
        
        throw new InvalidArgumentException(
            "recipient has to be either a string, WP_USER, array or stdclass."
        );
    }
    
    private static function isFormatted(string $recipient) :bool
    {
        return strpos($recipient, '<') !== false
               && strpos($recipient, '>') !== false;
    }
    
}