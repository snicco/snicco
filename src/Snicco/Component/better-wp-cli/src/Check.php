<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI;

use function array_filter;
use function array_values;
use function get_resource_type;
use function is_resource;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI
 */
final class Check
{
    /**
     * @psalm-assert-if-true  list<string> $value
     */
    public static function isListOfStrings(array $value): bool
    {
        $strings = array_filter($value, 'is_string') === $value;
        $list = array_values($value) === $value;

        return $strings && $list;
    }

    public static function isEmpty(array $value): bool
    {
        return [] === $value;
    }

    /**
     * @param mixed $value
     *
     * @psalm-assert-if-true resource $value
     */
    public static function isStream($value): bool
    {
        if (! is_resource($value)) {
            return false;
        }

        if ('stream' !== get_resource_type($value)) {
            return false;
        }

        return true;
    }
}
