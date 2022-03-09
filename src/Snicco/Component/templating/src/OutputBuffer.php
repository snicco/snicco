<?php

declare(strict_types=1);


namespace Snicco\Component\Templating;

use RuntimeException;

use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

/**
 * @psalm-internal Snicco\Component\Templating
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
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Output buffering could not be started.');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function get(): string
    {
        $output = ob_get_clean();
        if (false === $output) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Buffered output could not be retrieved.');
            // @codeCoverageIgnoreEnd
        }
        return $output;
    }

    /**
     * @throws RuntimeException
     */
    public static function remove(): void
    {
        $res = ob_end_clean();
        if (false === $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Buffered output could not be removed.');
            // @codeCoverageIgnoreEnd
        }
    }
}
