<?php

declare(strict_types=1);

namespace Snicco\Middleware\OpenRedirectProtection;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\StrArr\Str;

use function is_string;
use function parse_url;

use const PHP_URL_HOST;

/**
 * @todo Its currently possible to redirect to a whitelisted host from an external referer.
 * @todo It this a problem tho? Neither rails nor symfony have this feature.
 *       The only way we could prevent this is to sign all outgoing urls with a HMAC and then strip
 *       that from the query string before the redirect.
 */
final class OpenRedirectProtection extends Middleware
{
    /**
     * @var string[]
     */
    private array $whitelist;

    private string $host;

    private string $exit_path;

    /**
     * @param string[] $whitelist
     */
    public function __construct(string $host, string $exit_path, array $whitelist = [])
    {
        $parsed = parse_url($host, PHP_URL_HOST);
        if (!is_string($parsed) || '' === $parsed) {
            throw new InvalidArgumentException("Invalid host [$host].");
        }
        $this->host = $parsed;
        $this->exit_path = $exit_path;
        $this->whitelist = $this->formatWhiteList($whitelist);
        $this->whitelist[] = $this->allSubdomainsOfApplication();
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if (!$response->isRedirect()) {
            return $response;
        }

        if ($response instanceof RedirectResponse && $response->isExternalRedirectAllowed()) {
            return $response;
        }

        $target = $response->getHeaderLine('location');

        $is_same_site = $this->isSameSiteRedirect($request, $target);

        // Always allow relative redirects
        if ($is_same_site) {
            return $response;
        }

        $target_host = parse_url($target, PHP_URL_HOST);

        // Only allow redirects away to whitelisted hosts.
        if ($target_host && $this->isWhitelisted($target_host)) {
            return $response;
        }

        return $this->forbiddenRedirect($target);
    }

    /**
     * @return string[]
     */
    private function formatWhiteList(array $whitelist): array
    {
        return array_map(function (string $pattern) {
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

    private function isSameSiteRedirect(Request $request, string $location): bool
    {
        $parsed = parse_url($location);
        $target = $parsed['host'] ?? null;

        if (!$target && isset($parsed['path'])) {
            return true;
        }

        return $request->getUri()->getHost() === $target;
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
        return $this->redirect()->to($this->exit_path, 302, ['intended_redirect' => $location]);
    }

}