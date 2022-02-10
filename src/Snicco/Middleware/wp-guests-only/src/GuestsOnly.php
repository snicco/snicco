<?php

declare(strict_types=1);

namespace Snicco\Middleware\GuestsOnly;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Middleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\ScopableWP\ScopableWP;

/**
 * @api
 */
final class GuestsOnly extends Middleware
{

    private ScopableWP $wp;
    private ?string $redirect_to;
    private string $json_message;

    public function __construct(
        string $redirect_to = null,
        string $json_message = null,
        ScopableWP $wp = null
    ) {
        $this->redirect_to = $redirect_to;
        $this->json_message = $json_message ?: 'You are already authenticated';
        $this->wp = $wp ?: new ScopableWP();
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if (false === $this->wp->isUserLoggedIn()) {
            return $next($request);
        }

        if ($request->isExpectingJson()) {
            return $this->respond()
                ->json(['message' => $this->json_message], 403);
        }

        if ($this->redirect_to) {
            return $this->redirect()->to($this->redirect_to);
        } else {
            try {
                return $this->redirect()->toRoute('dashboard');
            } catch (RouteNotFound $e) {
                return $this->redirect()->home();
            }
        }
    }

}
