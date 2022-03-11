<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\StrArr\Arr;

use function in_array;

final class ControllerMiddleware
{
    /**
     * @var class-string<MiddlewareInterface>[]
     */
    private array $middleware_classes = [];

    /**
     * Methods the middleware applies to.
     *
     * @var string[]
     */
    private array $whitelist = [];

    /**
     * Methods the middleware does not apply to.
     *
     * @var string[]
     */
    private array $blacklist = [];

    /**
     * @param class-string<MiddlewareInterface>[] $middleware
     */
    public function __construct(array $middleware)
    {
        $this->middleware_classes = $middleware;
    }

    /**
     * @param string|string[] $methods
     */
    public function toMethods($methods): void
    {
        $this->whitelist = Arr::toArray($methods);
    }

    /**
     * @interal
     *
     * @psalm-mutation-free
     */
    public function appliesTo(string $method): bool
    {
        if (in_array($method, $this->blacklist, true)) {
            return false;
        }

        if (empty($this->whitelist)) {
            return true;
        }

        return in_array($method, $this->whitelist, true);
    }

    /**
     * @param string|string[] $methods
     */
    public function exceptForMethods($methods): void
    {
        $this->blacklist = Arr::toArray($methods);
    }

    /**
     * @interal
     *
     * @return class-string<MiddlewareInterface>[]
     */
    public function toArray(): array
    {
        return $this->middleware_classes;
    }
}
