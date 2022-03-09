<?php

declare(strict_types=1);


namespace Snicco\Middleware\WPNonce;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Psr7ErrorHandler\HttpException;

use function date;
use function sha1;

final class VerifyWPNonce extends Middleware
{
    private BetterWPAPI $wp;

    public function __construct(BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    public static function inputKey(): string
    {
        $month = date('m');
        return sha1(self::class . $month);
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $current_path = $request->path();

        if ($request->isReadVerb()) {
            $response = $next($request);
            if (! $response instanceof ViewResponse) {
                return $response;
            }

            return $response->withViewData(
                [
                    'wp_nonce' => new WPNonce($this->url(), $this->wp, $current_path),
                ]
            );
        }

        $nonce = (string) $request->post(self::inputKey(), '');

        if (! $this->wp->verifyNonce($nonce, $current_path)) {
            throw new HttpException(401, "Nonce check failed for request path [$current_path].");
        }

        return $next($request);
    }
}
