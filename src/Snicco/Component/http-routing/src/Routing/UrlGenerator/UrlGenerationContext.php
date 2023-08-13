<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Webmozart\Assert\Assert;
use function parse_url;

final class UrlGenerationContext
{
    private string $host;

    private int $https_port;

    private int $http_port;

    private bool $https_by_default;

    public function __construct(
        string $host,
        int $https_port = 443,
        int $http_port = 80,
        bool $https_by_default = true
    ) {
        Assert::stringNotEmpty($host, '$host cant be empty.');
        Assert::notContains($host, 'http', '$host must not contain a scheme.');
        Assert::notContains($host, '://', '$host must not contain a scheme.');
        $this->host = $host;

        Assert::positiveInteger($https_port);
        $this->https_port = $https_port;

        Assert::positiveInteger($http_port);
        $this->http_port = $http_port;
        $this->https_by_default = $https_by_default;
    }

    public static function fromUrlAndParts(
        string $url,
        ?string $host = null,
        ?int $https_port = null,
        ?int $http_port = null,
        ?bool $https_by_default = null
    ): self {
        $parts = parse_url($url);
        Assert::isArray($parts, "{$url} is not a valid url.");
        Assert::keyExists($parts, 'host', "{$url} is not a valid url. 'host' is missing.");
        Assert::keyExists($parts, 'scheme', "{$url} is not a valid url. 'scheme' is missing.");

        /** @var array{host: string, scheme: string, port?: int} $parts */
        $host = $host ?? $parts['host'];
        $https_port = $https_port ?? $parts['port'] ?? 443;
        $http_port = $http_port ?? $https_port;
        $https_by_default = $https_by_default ?? 'https' === $parts['scheme'];

        return new self(
            $host,
            $https_port,
            $http_port,
            $https_by_default
        );
    }

    public function host(): string
    {
        return $this->host;
    }

    public function httpsPort(): int
    {
        return $this->https_port;
    }

    public function httpPort(): int
    {
        return $this->http_port;
    }

    public function httpsByDefault(): bool
    {
        return $this->https_by_default;
    }
}
