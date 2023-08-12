<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\ResponseEmitter;

use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Log\LoggerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function ob_get_level;

/**
 * This emitter wraps the laminas/http-handler-runner package.
 * It has the following features:
 *  - If the request wants content-ranges or if the response is a streamed download the response will be streamed
 *  - If the request does not need to be streamed the content will be sent in one go.
 *  - If output has already been sent an exception is thrown.
 *
 * @codeCoverageIgnore
 */
final class LaminasEmitterStack implements ResponseEmitter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function emit(Response $response): void
    {
        if (ob_get_level() > 0) {
            $this->logger->warning(
                'Output buffering is turned on while sending a response. WordPress (plugins) might be modifying the final response.'
            );
            $response = $response->withoutHeader('Content-Length');
        }

        $stack = new EmitterStack();
        $stack->push(new SapiEmitter());

        if ($response->hasHeader('Content-Disposition') || $response->hasHeader('Content-Range')) {
            $stack->push(new SapiStreamEmitter());
        }

        $stack->emit($response);
    }
}
