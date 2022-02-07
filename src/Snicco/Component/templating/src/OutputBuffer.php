<?php

declare(strict_types=1);


namespace Snicco\Component\Templating;

use RuntimeException;

use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

/**
 * @interal
 * @codeCoverageIgnore
 */
final class OutputBuffer
{
    /**
     * @throws RuntimeException
     */
    public static function start(): void
    {
        $res = ob_start();
        if (false === $res) {
            throw new RuntimeException('Output buffering could not be started.');
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function get(): string
    {
        $output = ob_get_clean();
        if (false === $output) {
            throw new RuntimeException('Buffered output could not be retrieved.');
        }
        return $output;
    }

    public static function remove(): void
    {
        $res = ob_end_clean();
        if (false === $res) {
            throw new RuntimeException('Buffered output could not be removed.');
        }
    }
}