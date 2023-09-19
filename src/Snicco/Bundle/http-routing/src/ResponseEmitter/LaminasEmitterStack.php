<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\ResponseEmitter;

use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Log\LoggerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function ini_get;
use function is_numeric;
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
        $ob_level = ob_get_level();

        if ($ob_level > 0) {
            if ($ob_level > 1 || ! $this->hasOutputBufferingEnabledInIni()) {
                $this->warn($ob_level);
            }

            $response = $response->withoutHeader('Content-Length');
        }

        $stack = new EmitterStack();
        $stack->push(new SapiEmitter());

        if ($response->hasHeader('Content-Disposition') || $response->hasHeader('Content-Range')) {
            $stack->push(new SapiStreamEmitter());
        }

        $stack->emit($response);
    }

    private function hasOutputBufferingEnabledInIni(): bool
    {
        // "On/Off" are returned as "1" and "0" respectively.
        // See: https://www.php.net/manual/en/outcontrol.configuration.php#ini.output-buffering
        $buffering_in_ini = (string) @ini_get('output_buffering');

        return is_numeric($buffering_in_ini) && (int) $buffering_in_ini > 0;
    }

    private function warn(int $level): void
    {
        $this->logger->warning(
            "Output buffering is turned (ob_get_level={$level}) on while sending a response. WordPress (plugins) might be modifying the final response."
        );
    }
}
