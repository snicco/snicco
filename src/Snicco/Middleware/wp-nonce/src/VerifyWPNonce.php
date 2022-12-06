<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPNonce;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Middleware\WPNonce\Middleware\AddWPNonceToView;
use Snicco\Middleware\WPNonce\Middleware\CheckWPNonce;

/**
 * @deprecated This middleware is deprecated in favor of {@see AddWPNonceToView} and {@see CheckWPNonce} as splitting
 *             these responsibilities into separate middleware has many benefits {@see https://github.com/snicco/snicco/issues/167}.
 */
final class VerifyWPNonce extends Middleware
{
    private BetterWPAPI $wp;

    public function __construct(BetterWPAPI $wp = null)
    {
        $this->wp = $wp ?: new BetterWPAPI();
    }

    public static function inputKey(): string
    {
        return CheckWPNonce::inputKey();
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $check_nonce = new CheckWPNonce($this->wp);
        $this->setContainerOnInnerMiddleware($check_nonce);

        $add_wp_nonce = new AddWPNonceToView($this->wp);
        $this->setContainerOnInnerMiddleware($add_wp_nonce);

        return $add_wp_nonce->process(
            $request,
            new NextMiddleware(fn (Request $request) => $check_nonce->process($request, $next))
        );
    }
}
