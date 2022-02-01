<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Webmozart\Assert\Assert;

/**
 * @interal
 */
final class UrlGenerationContext
{

    private string $host;
    private string $current_scheme;
    private string $current_uri_as_string;
    private int $https_port;
    private int $http_port;
    private bool $should_force_https;
    private ?string $referer;
    private string $current_path;

    private function __construct(
        string $host,
        string $current_scheme,
        string $current_path,
        string $current_uri_as_string,
        int $https_port = 443,
        int $http_port = 80,
        bool $force_https = false,
        ?string $referer = null
    ) {
        Assert::stringNotEmpty($host, '$host cant be empty.');
        Assert::contains($host, '.', 'Expected host to contain a [.].');
        if (parse_url($current_uri_as_string, PHP_URL_HOST) !== $host) {
            throw new InvalidArgumentException(
                '$host and $current_uri_as_string are not compatible because the http host is different.'
            );
        }
        $this->host = $host;

        Assert::oneOf(
            $current_scheme,
            ['http', 'https'],
            'The scheme for url generation has to be either http or https.'
        );
        $this->current_scheme = $current_scheme;

        Assert::stringNotEmpty($current_path, '$current_path can not be empty.');
        Assert::startsWith($current_path, '/', '$current_path must start with [/].');
        $this->current_path = $current_path;

        // url scheme take from: https://ihateregex.io/expr/url/
        Assert::regex(
            $current_uri_as_string,
            '/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.~#?&\/\/=]*)/'
        );
        $this->current_uri_as_string = $current_uri_as_string;

        Assert::positiveInteger($https_port);
        $this->https_port = $https_port;

        Assert::positiveInteger($http_port);
        $this->http_port = $http_port;

        $this->referer = empty($referer) ? null : $referer;

        $this->should_force_https = $force_https;
    }

    public static function forConsole(
        string $host,
        bool $force_https = true,
        int $https_port = 443,
        int $http_port = 80,
        string $current_uri = null
    ): UrlGenerationContext {
        $scheme = $force_https ? 'https' : 'http';

        if (null === $current_uri) {
            $current_uri = $scheme . '://' . trim($host, '/') . '/';
        }

        return new self(
            $host,
            $scheme,
            '/',
            $current_uri,
            $https_port,
            $http_port,
            $force_https,
        );
    }

    public static function fromRequest(
        ServerRequestInterface $request,
        bool $should_force_https = false
    ): UrlGenerationContext {
        $uri = $request->getUri();

        $http_port = null;
        $https_port = null;

        if ('http' === $uri->getScheme()) {
            $http_port = $uri->getPort();
            if (is_null($http_port)) {
                $http_port = 80;
            }
        }

        if ('https' === $uri->getScheme()) {
            $https_port = $uri->getPort();
            if (is_null($https_port)) {
                $https_port = 443;
            }
        }

        return new self(
            $uri->getHost(),
            $uri->getScheme(),
            $uri->getPath() ?: '/',
            (string)$uri,
            $https_port ?? 443,
            $http_port ?? 80,
            $should_force_https,
            $request->getHeaderLine('referer')
        );
    }

    public function host(): string
    {
        return $this->host;
    }

    public function currentScheme(): string
    {
        return $this->current_scheme;
    }

    public function httpsPort(): int
    {
        return $this->https_port;
    }

    public function httpPort(): int
    {
        return $this->http_port;
    }

    public function referer(): ?string
    {
        return $this->referer;
    }

    public function shouldForceHttps(): bool
    {
        return $this->should_force_https;
    }

    /**
     * @note
     * This class does not handle reverse proxy configuration or anything similar.
     * This needs to be handled higher up the stack. @see ServerRequestCreator::createUriFromArray()
     */
    public function isSecure(): bool
    {
        return 'https' === $this->current_scheme;
    }

    public function currentPathUrlEncoded(): string
    {
        return $this->current_path;
    }

    /**
     * @return string
     * @see UriInterface::__toString
     */
    public function currentUriAsString(): string
    {
        return $this->current_uri_as_string;
    }

}