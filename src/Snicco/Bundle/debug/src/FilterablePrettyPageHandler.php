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
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class FilterablePrettyPageHandler extends PrettyPageHandler
{

    protected function getExceptionFrames(): FrameCollection
    {
        $frames = parent::getExceptionFrames();

        $frames->filter(function (Frame $frame) {
            $class = (string)$frame->getClass();

            if ($class === NextMiddleware::class) {
                return false;
            }

            if ($class === MiddlewarePipeline::class) {
                return $frame->getFunction() !== 'runNext';
            }

            if ($class === Middleware::class) {
                return $frame->getFunction() !== 'process';
            }

            return true;
        });

        return $frames;
    }

}