<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Middleware;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Snicco\Bundle\Session\ValueObject\SessionErrors;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Session\MutableSession;

final class SaveResponseAttributes extends Middleware
{

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        $session = $request->getAttribute(MutableSession::class);

        if (!$session instanceof MutableSession) {
            throw new LogicException('No mutable session has been set on the request.');
        }

        $session->flash(SessionErrors::class, $response->errors());

        foreach ($response->flashMessages() as $key => $message) {
            $session->flash($key, $message);
        }

        $session->flashInput($response->oldInput());

        return $response;
    }
}