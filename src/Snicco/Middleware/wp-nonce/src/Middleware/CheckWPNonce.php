<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPNonce\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Middleware\WPNonce\Exception\InvalidWPNonce;

use function md5;

final class CheckWPNonce extends Middleware
{
    private BetterWPAPI $wp;

    public function __construct(?BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    public static function inputKey(): string
    {
        return md5(self::class);
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if ($request->isReadVerb()) {
            return $next($request);
        }

        $current_path = $request->path();
        $nonce = (string) $request->post(self::inputKey(), '');

        if (! $this->wp->verifyNonce($nonce, $current_path)) {
            throw InvalidWPNonce::forPath($current_path);
        }

        return $next($request);
    }
}
