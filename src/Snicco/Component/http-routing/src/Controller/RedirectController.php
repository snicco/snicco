<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;

/**
 * @interal
 * @psalm-suppress MixedArgument
 */
final class RedirectController extends Controller
{

    /**
     * @param mixed ...$args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function to(...$args): RedirectResponse
    {
        [$location, $status_code, $query] = array_slice($args, -3);

        return $this->redirect()->to($location, $status_code, $query);
    }

    /**
     * @param mixed ...$args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function away(...$args): RedirectResponse
    {
        [$location, $status_code] = array_slice($args, -2);

        return $this->redirect()->away($location, $status_code);
    }

    /**
     * @param mixed ...$args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function toRoute(...$args): RedirectResponse
    {
        [$route, $arguments, $status_code] = array_slice($args, -3);

        return $this->redirect()->toRoute($route, $arguments, $status_code);
    }

}