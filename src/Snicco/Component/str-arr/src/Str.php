<?php

/*
 * Trimmed down version of the Illuminate/Str class with the following modifications
 * - full multibyte support for all methods
 * - strict type hinting
 * - final class attribute
 * - way less permissive with invalid input like null values.
 * - removal of all hidden dependencies.
 * - removal of unneeded doc-blocks
 *
 * https://github.com/laravel/framework/blob/v8.35.1/src/Illuminate/Support/Str.php
 *
 * License: The MIT License (MIT) https://github.com/laravel/framework/blob/v8.35.1/LICENSE.md
 *
 * Copyright (c) Taylor Otwell
 *
 */

declare(strict_types=1);

namespace Snicco\Component\StrArr;

use Exception;
use RuntimeException;

use function array_map;
use function array_reverse;
use function bin2hex;
use function explode;
use function implode;
use function is_string;
use function mb_internal_encoding;
use function mb_strpos;
use function mb_strrpos;
use function mb_strtoupper;
use function mb_substr;
use function preg_last_error;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function random_bytes;
use function str_replace;
use function strlen;
use function strncmp;
use function strpos;
use function strrpos;
use function strstr;
use function substr;
use function substr_replace;

use const PREG_BACKTRACK_LIMIT_ERROR;
use const PREG_BAD_UTF8_ERROR;
use const PREG_BAD_UTF8_OFFSET_ERROR;
use const PREG_INTERNAL_ERROR;
use const PREG_RECURSION_LIMIT_ERROR;

class Str
{
    /**
     * @var array<string,string>
     */
    private static array $studly_cache = [];

    /**
     * @param list<string> $needles
     */
    public static function containsAll(string $subject, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (! self::contains($subject, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string|string[] $needles
     *
     * @psalm-pure
     */
    public static function contains(string $subject, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' !== $needle && false !== mb_strpos($subject, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $needles
     */
    public static function containsAny(string $subject, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (self::contains($subject, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a random secret with the passed bytes as strength.
     * The output is hex encoded and will have TWICE the length as $strength.
     *
     * @param positive-int $bytes
     *
     * @throws Exception
     */
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * "snicco_wp-framework" => "SniccoWpFramework".
     */
    public static function studly(string $value): string
    {
        $key = $value;

        if (isset(self::$studly_cache[$key])) {
            return self::$studly_cache[$key];
        }

        $parts = explode(' ', str_replace(['-', '_'], ' ', $value));

        $parts = array_map(fn ($string) => self::ucfirst($string), $parts);

        return self::$studly_cache[$key] = implode('', $parts);
    }

    public static function ucfirst(string $subject, ?string $encoding = null): string
    {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
            if (! is_string($encoding)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Internal multi-byte encoding not set.');
                // @codeCoverageIgnoreEnd
            }
        }

        return mb_strtoupper(mb_substr($subject, 0, 1, $encoding), $encoding) . mb_substr(
            $subject,
            1,
            null,
            $encoding
        );
    }

    public static function doesNotEndWith(string $subject, string $string): bool
    {
        return ! self::endsWith($subject, $string);
    }

    /**
     * @psalm-pure
     */
    public static function endsWith(string $subject, string $needle): bool
    {
        if ('' === $needle) {
            return false;
        }

        return substr($subject, -strlen($needle)) === $needle;
    }

    /**
     * @psalm-pure
     */
    public static function afterLast(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if (false === $position) {
            return $subject;
        }

        $res = substr($subject, $position + strlen($search));
        if (false === $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("substr returned false for subject [{$subject}].");
            // @codeCoverageIgnoreEnd
        }

        return $res;
    }

    /**
     * @psalm-pure
     */
    public static function startsWith(string $subject, string $needle): bool
    {
        if ('' === $needle) {
            return false;
        }

        return 0 === strncmp($subject, $needle, strlen($needle));
    }

    /**
     * Str::betweenLast('xayyy', 'x', 'y') => 'xayy'.
     */
    public static function betweenLast(string $subject, string $from, string $to): string
    {
        if ('' === $from || '' === $to) {
            return $subject;
        }

        return self::beforeLast(self::afterFirst($subject, $from), $to);
    }

    public static function beforeLast(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if (false === $pos) {
            return $subject;
        }

        return self::substr($subject, 0, $pos);
    }

    public static function substr(string $subject, int $start, int $length = null): string
    {
        return mb_substr($subject, $start, $length, 'UTF-8');
    }

    public static function afterFirst(string $subject, string $search): string
    {
        return '' === $search ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Str::betweenFirst('xayyy', 'x', 'y') => 'a'.
     */
    public static function betweenFirst(string $subject, string $from, string $to): string
    {
        if ('' === $from || '' === $to) {
            return $subject;
        }

        return self::beforeFirst(self::afterFirst($subject, $from), $to);
    }

    /**
     * @psalm-pure
     */
    public static function beforeFirst(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $result = strstr($subject, $search, true);

        return false === $result ? $subject : $result;
    }

    /**
     * @param string $pattern For convenience foo/* will be transformed to foo.*
     *
     * @psalm-pure
     */
    public static function is(string $subject, string $pattern): bool
    {
        // If the given value is an exact match we can of course return true right
        // from the beginning. Otherwise, we will translate asterisks and do an
        // actual pattern match against the two strings to see if they match.
        if ($pattern === $subject) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        if (1 === preg_match('#^' . $pattern . '\z#u', $subject)) {
            return true;
        }

        return false;
    }

    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strpos($subject, $search);

        if (false !== $position) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param string $subject regex delimiters will not be added
     * @psalm-pure
     */
    public static function pregReplace(string $subject, string $pattern, string $replace): string
    {
        $res = preg_replace($pattern . 'u', $replace, $subject);
        if (null === $res) {
            /** @psalm-suppress ImpureFunctionCall | This function is pure, but we run psalm with PHP7.4, so it does not take into account PURE annotations. */
            $code = preg_last_error();

            switch ($code) {
                case PREG_INTERNAL_ERROR:
                    $message = 'Internal Error';

                    break;
                case PREG_BACKTRACK_LIMIT_ERROR:
                    $message = 'Backtrack limit was exhausted';

                    break;
                case PREG_RECURSION_LIMIT_ERROR:
                    $message = 'Recursion limit was exhausted';

                    break;
                case PREG_BAD_UTF8_ERROR:
                    $message = 'Malformed UTF-8 data';

                    break;
                case PREG_BAD_UTF8_OFFSET_ERROR:
                    $message = 'Offset didn\'t correspond to the begin of a valid UTF-8 code point';

                    break;
                default:
                    $message = 'Unknown Error';
            }

            throw new RuntimeException(
                "preg_replace failed. {$message}\nPattern: [{$pattern}]\nReplacement: [{$pattern}].\nSubject: [{$subject}]."
            );
        }

        return $res;
    }
}
