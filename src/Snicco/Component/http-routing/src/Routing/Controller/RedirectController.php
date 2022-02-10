<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;

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

    /**
     * @todo remove this method here and add a template to the open redirect protection middleware.
     */
    public function exit(Request $request): void
    {
        $home_url = '/';
        try {
            $home_url = $this->url()->toRoute('home');
        } catch (RouteNotFound $e) {
            //
        }

        $this->render('framework.redirect-protection', [
            'untrusted_url' => $request->query('intended_redirect'),
            'home_url' => $home_url,
        ]);
    }

}