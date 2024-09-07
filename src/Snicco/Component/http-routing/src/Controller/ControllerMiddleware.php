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
    private array $middleware_classes;

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
     * @param class-string<MiddlewareInterface>|class-string<MiddlewareInterface>[] $middleware
     */
    public function __construct($middleware)
    {
        $this->middleware_classes = Arr::toArray($middleware);
    }

    /**
     * @param string|string[] $methods
     */
    public function except($methods): self
    {
        $this->whitelist = Arr::toArray($methods);

        return $this;
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
    public function only($methods): self
    {
        $this->blacklist = Arr::toArray($methods);

        return $this;
    }

    /**
     * @internal
     *
     * @return class-string<MiddlewareInterface>[]
     *
     * @see ControllerAction::middleware()
     */
    public function toArray(): array
    {
        return $this->middleware_classes;
    }
}
