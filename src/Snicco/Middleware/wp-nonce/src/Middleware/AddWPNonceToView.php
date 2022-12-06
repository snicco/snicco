<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPNonce\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Middleware\WPNonce\WPNonce;

final class AddWPNonceToView extends Middleware
{
    public const WP_NONCE_VIEW_NAME = 'wp_nonce';

    private BetterWPAPI $wp;

    public function __construct(?BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);
        if (! $response instanceof ViewResponse) {
            return $response;
        }

        return $response->withViewData([
            self::WP_NONCE_VIEW_NAME => new WPNonce($this->url(), $this->wp, $request->path()),
        ]);
    }
}
