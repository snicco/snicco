<?php

declare(strict_types=1);

namespace Snicco\Middleware\OpenRedirectProtection;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\StrArr\Str;

use function is_string;
use function parse_url;

use const PHP_URL_HOST;

final class OpenRedirectProtection extends Middleware
{
    /**
     * @var string[]
     */
    private array $whitelist = [];

    private string $host;

    private string $exit_path;

    /**
     * @param string[] $whitelist
     */
    public function __construct(string $host, string $exit_path, array $whitelist = [])
    {
        $parsed = parse_url($host, PHP_URL_HOST);
        if (! is_string($parsed) || '' === $parsed) {
            throw new InvalidArgumentException(sprintf('Invalid host [%s].', $host));
        }

        $this->host = $parsed;
        $this->exit_path = $exit_path;
        $this->whitelist = $this->formatWhiteList($whitelist);
        $this->whitelist[] = $this->allSubdomainsOfApplication();
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if (! $response->isRedirect()) {
            return $response;
        }

        if ($response instanceof RedirectResponse && $response->isExternalRedirectAllowed()) {
            return $response;
        }

        $target = $response->getHeaderLine('location');

        $parts = (array) parse_url($target);
        $parts['host'] ??= '';

        // Always allow relative redirects
        if ($this->isSameSiteRedirect($request->getUri(), $parts['host'])) {
            return $response;
        }

        if (! $this->isWhitelisted($parts['host'])) {
            return $this->forbiddenRedirect($target);
        }

        return $response;
    }

    /**
     * @return string[]
     */
    private function formatWhiteList(array $whitelist): array
    {
        return array_map(function (string $pattern): string {
            if (Str::startsWith($pattern, '*.')) {
                return $this->allSubdomains(Str::afterFirst($pattern, '*.'));
            }

            return '/' . preg_quote($pattern, '/') . '/';
        }, $whitelist);
    }

    private function allSubdomains(string $host): string
    {
        return '/^(.+\.)?' . preg_quote($host, '/') . '$/';
    }

    private function allSubdomainsOfApplication(): string
    {
        return $this->allSubdomains($this->host);
    }

    private function isSameSiteRedirect(UriInterface $uri, string $redirect_host): bool
    {
        if ('' === $redirect_host) {
            return true;
        }

        return $uri->getHost() === $redirect_host;
    }

    private function isWhitelisted(string $host): bool
    {
        foreach ($this->whitelist as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    private function forbiddenRedirect(string $location): RedirectResponse
    {
        return $this->respondWith()
            ->redirectTo($this->exit_path, 302, [
                'intended_redirect' => $location,
            ]);
    }
}
