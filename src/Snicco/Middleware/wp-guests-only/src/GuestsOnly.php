<?php

declare(strict_types=1);

namespace Snicco\Middleware\GuestsOnly;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;

final class GuestsOnly extends Middleware
{
    private BetterWPAPI $wp;

    private ?string $redirect_to;

    private string $json_message;

    public function __construct(
        string $redirect_to = null,
        string $json_message = null,
        BetterWPAPI $wp = null
    ) {
        $this->redirect_to = $redirect_to;
        $this->json_message = $json_message ?: 'You are already authenticated';
        $this->wp = $wp ?: new BetterWPAPI();
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if (false === $this->wp->isUserLoggedIn()) {
            return $next($request);
        }

        if ($request->isExpectingJson()) {
            return $this->respondWith()->json([
                'message' => $this->json_message,
            ], 403);
        }

        if ($this->redirect_to) {
            return $this->respondWith()->redirectTo($this->redirect_to);
        } else {
            try {
                return $this->respondWith()->redirectToRoute('dashboard');
            } catch (RouteNotFound $e) {
                return $this->respondWith()->redirectHome();
            }
        }
    }
}
