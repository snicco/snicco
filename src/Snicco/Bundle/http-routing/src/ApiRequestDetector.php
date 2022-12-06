<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function array_map;
use function is_string;
use function ltrim;

/**
 * @psalm-internal Snicco
 */
final class ApiRequestDetector
{
    /**
     * @var non-empty-string[]
     */
    private array $api_prefixes;

    /**
     * @param non-empty-string[]|non-empty-string $early_route_prefixes
     */
    public function __construct($early_route_prefixes)
    {
        $early_route_prefixes = Arr::toArray($early_route_prefixes);

        Assert::allNotEq($early_route_prefixes, '/');

        $early_route_prefixes = array_map(fn (string $prefix) => '/' . ltrim($prefix, '/'), $early_route_prefixes);

        $this->api_prefixes = $early_route_prefixes;
    }

    /**
     * @param string|ServerRequestInterface $path_or_request
     */
    public function isAPIRequest($path_or_request): bool
    {
        $path = is_string($path_or_request) ? $path_or_request : $path_or_request->getUri()
            ->getPath();

        foreach ($this->api_prefixes as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
