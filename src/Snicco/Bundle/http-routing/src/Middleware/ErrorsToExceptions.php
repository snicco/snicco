<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

use const E_ALL;
use const E_DEPRECATED;
use const E_USER_DEPRECATED;

/**
 * This middleware will convert all errors that are not deprecations to proper exceptions.
 * However, this only happens inside the middleware pipeline meaning that other WordPress that we can't control nor change
 * is unaffected by this.
 */
final class ErrorsToExceptions implements MiddlewareInterface
{

    private LoggerInterface $logger;

    private int $throw_at = E_ALL - E_DEPRECATED - E_USER_DEPRECATED;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        set_error_handler(function (int $level, string $message, string $file = '', int $line = 0): bool {
            if ($this->throw_at & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
            // Don't pass the deprecation to PHPs native error handler since it will display them with display errors set to one.
            // This will cause the laminas' response emitter to throw an exception because of previous output.
            $line = (string)$line;
            $this->logger->info("PHP Deprecated: $message in $file on line $line");
            return true;
        });

        try {
            return $handler->handle($request);
        } finally {
            restore_error_handler();
        }
    }
}