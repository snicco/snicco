<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPAuth;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Psr7ErrorHandler\HttpException;

use function sprintf;

final class AuthenticateWPUser extends Middleware
{
    private BetterWPAPI $wp;

    public function __construct(BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if ($this->wp->isUserLoggedIn()) {
            return $next($request);
        }

        throw new HttpException(
            401,
            sprintf('Missing authentication for request path [%s].', $request->getUri()->getPath())
        );
    }
}
