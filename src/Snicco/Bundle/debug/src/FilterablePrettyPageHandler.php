<?php

declare(strict_types=1);

namespace Snicco\Bundle\Debug;

use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Whoops\Exception\Frame;
use Whoops\Exception\FrameCollection;
use Whoops\Handler\PrettyPageHandler;

/**
 * @internal
 *
 * @psalm-internal Snicco\Bundle\Debug
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class FilterablePrettyPageHandler extends PrettyPageHandler
{
    protected function getExceptionFrames(): FrameCollection
    {
        $frames = parent::getExceptionFrames();

        $frames->filter(function (Frame $frame): bool {
            $class = (string) $frame->getClass();

            if (NextMiddleware::class === $class) {
                return false;
            }

            if (MiddlewarePipeline::class === $class) {
                return 'runNext' !== $frame->getFunction();
            }

            if (Middleware::class === $class) {
                return 'process' !== $frame->getFunction();
            }

            return true;
        });

        return $frames;
    }
}
