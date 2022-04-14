<?php

/*
 * This class is a derivative work of the Illuminate/Str class with the following modifications:
 * - full multibyte support for all methods
 * - strict type hinting
 * - final class attribute
 * - way less permissive with invalid input like null values.
 * - removal of all hidden dependencies.
 * - removal of unneeded doc-blocks
 * - support for psalm
 *
 * The illuminate/support package is licensed under the MIT License:
 * https://github.com/laravel/framework/blob/v8.35.1/LICENSE.md
 *
 * The MIT License (MIT)
 *
 * Copyright (c) Taylor Otwell
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace Snicco\Component\StrArr;

use RuntimeException;

use function array_map;
use function array_reverse;
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

final class Str
{
    /**
     * @var array<string,string>
     */
    private static array $studly_cache = [];

    /**
     * @psalm-pure
     */
    public static function contains(string $subject, string $substring): bool
    {
        if ('' === $substring) {
            return false;
        }

        if ('' === $subject) {
            return false;
        }

        return false !== mb_strpos($subject, $substring);
    }

    /**
     * @param list<string> $substrings
     *
     * @psalm-pure
     */
    public static function containsAll(string $subject, array $substrings): bool
    {
        foreach ($substrings as $substring) {
            if (! self::contains($subject, $substring)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $substrings
     *
     * @psalm-pure
     */
    public static function containsAny(string $subject, array $substrings): bool
    {
        foreach ($substrings as $needle) {
            if (self::contains($subject, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method converts: "snicco_wp-framework" => "sniccoFramework".
     *
     * @psalm-external-mutation-free
     */
    public static function studly(string $value): string
    {
        $key = $value;

        if (isset(self::$studly_cache[$key])) {
            return self::$studly_cache[$key];
        }

        $parts = explode(' ', str_replace(['-', '_'], ' ', $value));

        $parts = array_map(fn ($string): string => self::ucfirst($string), $parts);

        return self::$studly_cache[$key] = implode('', $parts);
    }

    /**
     * @psalm-pure
     */
    public static function ucfirst(string $subject, ?string $encoding = null): string
    {
        if (null === $encoding) {
            /** @psalm-suppress ImpureFunctionCall */
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

    /**
     * @psalm-pure
     */
    public static function startsWith(string $subject, string $substring): bool
    {
        if ('' === $substring) {
            return false;
        }

        return 0 === strncmp($subject, $substring, strlen($substring));
    }

    /**
     * @psalm-pure
     */
    public static function endsWith(string $subject, string $substring): bool
    {
        if ('' === $substring) {
            return false;
        }

        return substr($subject, -strlen($substring)) === $substring;
    }

    /**
     * @psalm-pure
     */
    public static function doesNotEndWith(string $subject, string $substring): bool
    {
        return ! self::endsWith($subject, $substring);
    }

    /**
     * @psalm-pure
     */
    public static function afterFirst(string $subject, string $search): string
    {
        return '' === $search ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * @psalm-pure
     */
    public static function afterLast(string $subject, string $substring): string
    {
        if ('' === $substring) {
            return $subject;
        }

        $position = strrpos($subject, $substring);

        if (false === $position) {
            return $subject;
        }

        $res = substr($subject, $position + strlen($substring));
        if (false === $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('substr returned false for subject [%s].', $subject));
            // @codeCoverageIgnoreEnd
        }

        return $res;
    }

    /**
     * Usage: Str::betweenFirst('xayyy', 'x', 'y') => 'a'.
     *
     * @psalm-pure
     */
    public static function betweenFirst(string $subject, string $from, string $to): string
    {
        if ('' === $from) {
            return $subject;
        }

        if ('' === $to) {
            return $subject;
        }

        return self::beforeFirst(self::afterFirst($subject, $from), $to);
    }

    /**
     * Usage: Str::betweenLast('xayyy', 'x', 'y') => 'xayy'.
     *
     * @psalm-pure
     */
    public static function betweenLast(string $subject, string $from, string $to): string
    {
        if ('' === $from) {
            return $subject;
        }

        if ('' === $to) {
            return $subject;
        }

        return self::beforeLast(self::afterFirst($subject, $from), $to);
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
     * @psalm-pure
     */
    public static function beforeLast(string $subject, string $substring): string
    {
        if ('' === $substring) {
            return $subject;
        }

        $pos = mb_strrpos($subject, $substring);

        if (false === $pos) {
            return $subject;
        }

        return self::substr($subject, 0, $pos);
    }

    /**
     * @psalm-pure
     */
    public static function substr(string $subject, int $start, int $length = null): string
    {
        return mb_substr($subject, $start, $length, 'UTF-8');
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

        return 1 === preg_match('#^' . $pattern . '\z#u', $subject);
    }

    /**
     * @psalm-pure
     */
    public static function replaceFirst(string $subject, string $substring, string $replace): string
    {
        if ('' === $substring) {
            return $subject;
        }

        $position = strpos($subject, $substring);

        if (false !== $position) {
            return substr_replace($subject, $replace, $position, strlen($substring));
        }

        return $subject;
    }

    /**
     * @psalm-pure
     */
    public static function replaceAll(string $subject, string $substring, string $replace): string
    {
        return str_replace($substring, $replace, $subject);
    }

    /**
     * @param string $subject regex delimiters will not be added
     *
     * @psalm-pure
     */
    public static function pregReplace(string $subject, string $pattern, string $replace): string
    {
        $res = preg_replace($pattern . 'u', $replace, $subject);
        if (null === $res) {
            /**
             * This function is pure, but we run psalm with PHP7.4, so it does
             * not take into account PURE annotations.
             *
             * @psalm-suppress ImpureFunctionCall
             */
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
                    $message = "Offset didn't correspond to the begin of a valid UTF-8 code point";

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
