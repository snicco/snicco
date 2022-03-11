<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;

use function array_slice;

/**
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class RedirectController extends Controller
{
    /**
     * @param mixed ...$args
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedArgument
     */
    public function to(...$args): RedirectResponse
    {
        [$location, $status_code, $query] = array_slice($args, -3);

        return $this->respondWith()
            ->redirectTo($location, $status_code, $query);
    }

    /**
     * @param mixed ...$args
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedArgument
     */
    public function away(...$args): RedirectResponse
    {
        [$location, $status_code] = array_slice($args, -2);

        return $this->respondWith()
            ->externalRedirect($location, $status_code);
    }

    /**
     * @param mixed ...$args
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedArgument
     */
    public function toRoute(...$args): RedirectResponse
    {
        [$route, $arguments, $status_code] = array_slice($args, -3);

        return $this->respondWith()
            ->redirectToRoute($route, $arguments, $status_code);
    }
}
