<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPNonce;

use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use function htmlentities;
use function ltrim;

use const ENT_QUOTES;

final class WPNonce
{
    private UrlGenerator $generator;

    private string $current_path;

    private BetterWPAPI $wp;

    public function __construct(UrlGenerator $generator, BetterWPAPI $wp, string $current_path)
    {
        $this->generator = $generator;
        $this->current_path = $current_path;
        $this->wp = $wp;
    }

    /**
     * @param array<string,int|string> $args
     */
    public function __invoke(string $route_name = null, array $args = []): string
    {
        if (null === $route_name) {
            $nonce_action = $this->current_path;
        } else {
            try {
                $nonce_action = $this->generator->toRoute($route_name, $args);
            } catch (RouteNotFound $e) {
                $nonce_action = '/' . ltrim($route_name, '/');
            }
        }

        return $this->createNonce($nonce_action);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    private function createNonce(string $nonce_action): string
    {
        $nonce = $this->noHtml($this->wp->createNonce($nonce_action));
        $name = VerifyWPNonce::inputKey();

        return sprintf("<input type='hidden' name='%s' value='%s'>", $name, $nonce);
    }

    private function noHtml(string $nonce): string
    {
        return htmlentities($nonce, ENT_QUOTES, 'UTF-8');
    }
}
