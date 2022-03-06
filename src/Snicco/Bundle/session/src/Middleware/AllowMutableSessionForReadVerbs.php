<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Middleware;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\Session\ImmutableSession;

use function sprintf;

/**
 * This middleware can be used on a per-route basis to allow access to a "write" session for GET requests.
 * It must run BEFORE the StatefulRequest middleware.
 */
final class AllowMutableSessionForReadVerbs implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute(ImmutableSession::class)) {
            throw new LogicException(
                sprintf(
                    "A session has already been set on the request.\nMake sure that the [%s] middleware is run before the [%s] middleware.",
                    __CLASS__,
                    StatefulRequest::class
                )
            );
        }

        return $handler->handle($request->withAttribute(StatefulRequest::ALLOW_WRITE_SESSION_FOR_READ_VERBS, true));
    }
}