<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Webmozart\Assert\Assert;

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
        Assert::contains($host, '.', 'Expected $host to contain a [.].');
        Assert::notContains($host, 'http', '$host must not contain a scheme.');
        Assert::notContains($host, '://', '$host must not contain a scheme.');
        $this->host = $host;

        Assert::positiveInteger($https_port);
        $this->https_port = $https_port;

        Assert::positiveInteger($http_port);
        $this->http_port = $http_port;
        $this->https_by_default = $https_by_default;
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
