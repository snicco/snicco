<?php

/*
 * This class is slightly version of symfony/console's Terminal class.
 * symfony/console is licensed with the MIT License.
 *
 * This class has way superior support for detecting the Terminal
 * attributes of the Terminal than wp-cli/php-cli-tools.
 *
 * wp-cli is currently locked to wp-cli/php-cli-tools:~0.11.2 which is a release made in 2017.
 */

/*
 * Copyright (c) 2004-2022 Fabien Potencier
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the 'Software'), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Style;

use function function_exists;
use function is_resource;
use const DIRECTORY_SEPARATOR;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\BetterWPCLI
 *
 * @codeCoverageIgnore
 */
final class Terminal
{
    private static ?int $width = null;

    private static ?int $height = null;

    private static ?bool $stty = null;

    public static function width(): int
    {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int) trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?: 120;
    }

    public static function height(): int
    {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int) trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?: 50;
    }

    public static function hasSttyAvailable(): bool
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        // skip check if exec function is disabled
        if (! function_exists('exec')) {
            return false;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = 0 === $exitcode;
    }

    private static function initDimensions(): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            if (preg_match('#^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$#', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int) $matches[1];
                self::$height = isset($matches[4]) ? (int) $matches[4] : (int) $matches[2];
            } elseif (! self::hasVt100Support() && self::hasSttyAvailable()) {
                // only use stty on Windows if the terminal does not support vt100 (e.g. Windows 7 + git-bash)
                // testing for stty in a Windows 10 vt100-enabled console will implicitly disable vt100 support on STDOUT
                self::initDimensionsUsingStty();
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int) $dimensions[0];
                self::$height = (int) $dimensions[1];
            }
        } else {
            self::initDimensionsUsingStty();
        }
    }

    /**
     * Returns whether STDOUT has vt100 support (some Windows 10+
     * configurations).
     */
    private static function hasVt100Support(): bool
    {
        return function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(fopen('php://stdout', 'w'));
    }

    /**
     * Initializes dimensions using the output of an stty columns line.
     */
    private static function initDimensionsUsingStty(): void
    {
        if ($sttyString = self::getSttyColumns()) {
            if (preg_match('#rows.(\d+);.columns.(\d+);#i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            } elseif (preg_match('#;.(\d+).rows;.(\d+).columns#i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            }
        }
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return int[]|null An array composed of the width and the height or null if it could not be parsed
     */
    private static function getConsoleMode(): ?array
    {
        $info = self::readFromProcess('mode CON');
        if (null === $info) {
            return null;
        }

        if (! preg_match('#--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n#', $info, $matches)) {
            return null;
        }

        return [(int) $matches[2], (int) $matches[1]];
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     */
    private static function getSttyColumns(): ?string
    {
        return self::readFromProcess('stty -a | grep columns');
    }

    private static function readFromProcess(string $command): ?string
    {
        if (! function_exists('proc_open')) {
            return null;
        }

        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes, null, null, [
            'suppress_errors' => true,
        ]);
        if (! is_resource($process)) {
            return null;
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $info;
    }
}
