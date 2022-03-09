<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

final class SetUserId extends Middleware
{
    private BetterWPAPI $wp;

    public function __construct(BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $user_id = $this->wp->currentUserId();

        return $next($request->withUserId($user_id));
    }
}
