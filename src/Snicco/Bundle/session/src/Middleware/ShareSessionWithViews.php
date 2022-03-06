<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Middleware;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Snicco\Bundle\Session\ValueObject\SessionErrors;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Session\ImmutableSession;

final class ShareSessionWithViews extends Middleware
{

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        $session = $request->getAttribute(ImmutableSession::class);

        if (!$session instanceof ImmutableSession) {
            throw new LogicException('No session has been set on the request.');
        }

        if (!$response instanceof ViewResponse) {
            return $response;
        }
        
        $errors = (array)$session->get(SessionErrors::class, []);

        return $response->withViewData([
            'session' => $session,
            'errors' => new SessionErrors($errors)
        ]);
    }
}