<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Cache;

final class CallbackRouteCache implements RouteCache
{
    /**
     * @var callable(callable():array):array
     */
    private $loader;

    /**
     * @param callable(callable():array):array $loader
     */
    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function get(callable $loader): array
    {
        return ($this->loader)($loader);
    }
}
