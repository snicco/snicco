<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use LogicException;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\StrArr\Arr;

final class ControllerMiddleware
{

    /**
     * @var class-string<MiddlewareInterface>
     */
    private string $middleware_class;

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
     * @param class-string<MiddlewareInterface> $middleware
     */
    public function __construct(string $middleware)
    {
        $this->middleware_class = $middleware;
    }

    /**
     * Set methods the middleware should apply to.
     *
     * @param string|string[] $methods
     *
     * @throws LogicException
     */
    public function only($methods): void
    {
        if (!empty($this->blacklist)) {
            throw new LogicException(
                'The only() method cant be combined with the except() method for one middleware'
            );
        }
        $this->whitelist = Arr::toArray($methods);
    }

    /**
     * Set methods the middleware should not apply to.
     *
     * @param string|string[] $methods
     *
     * @throws LogicException
     */
    public function except($methods): void
    {
        if (!empty($this->whitelist)) {
            throw new LogicException(
                'The only() method cant be combined with the except() method for one middleware'
            );
        }

        $this->blacklist = Arr::toArray($methods);
    }

    /**
     * @psalm-mutation-free
     * @interal
     */
    public function appliesTo(string $method = null): bool
    {
        if (in_array($method, $this->blacklist, true)) {
            return false;
        }

        if (empty($this->whitelist)) {
            return true;
        }

        return in_array($method, $this->whitelist, true);
    }

    public function name(): string
    {
        return $this->middleware_class;
    }

}