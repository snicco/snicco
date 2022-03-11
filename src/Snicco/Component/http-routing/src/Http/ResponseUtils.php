<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use JsonException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use function array_replace;

final class ResponseUtils
{
    private UrlGenerator $url_generator;

    private ResponseFactory $response_factory;

    private Request $current_request;

    public function __construct(
        UrlGenerator $url_generator,
        ResponseFactory $response_factory,
        Request $current_request
    ) {
        $this->url_generator = $url_generator;
        $this->response_factory = $response_factory;
        $this->current_request = $current_request;
    }

    /**
     * Redirects to a path on the same domain.
     *
     * @param array<string,int|string> $extra
     *
     * @see UrlGenerator::to()
     */
    public function redirectTo(string $path, int $status_code = 302, array $extra = []): RedirectResponse
    {
        $location = $this->url_generator->to($path, $extra);

        return $this->response_factory->redirect($location, $status_code);
    }

    /**
     * @param array<string,int|string> $arguments
     *
     * @throws RouteNotFound
     *
     * @see UrlGenerator::toRoute()
     */
    public function redirectToRoute(string $name, array $arguments = [], int $status_code = 302): RedirectResponse
    {
        $location = $this->url_generator->toRoute($name, $arguments);

        return $this->redirectTo($location, $status_code);
    }

    /**
     * Tries to create a redirect response to a "home" route and falls back to
     * "/" if no home route exists.
     *
     * @param array<string,int|string> $arguments
     */
    public function redirectHome(array $arguments = [], int $status_code = 302): RedirectResponse
    {
        try {
            $location = $this->url_generator->toRoute('home', $arguments);
        } catch (RouteNotFound $e) {
            $location = $this->url_generator->to('/', $arguments);
        }

        return $this->redirectTo($location, $status_code);
    }

    /**
     * @param array<string,int|string> $arguments
     *
     * @see UrlGenerator::toLogin()
     */
    public function redirectToLogin(array $arguments = [], int $status_code = 302): RedirectResponse
    {
        return $this->redirectTo($this->url_generator->toLogin($arguments), $status_code);
    }

    /**
     * Redirect to the current url.
     */
    public function refresh(): RedirectResponse
    {
        return $this->redirectTo((string) $this->current_request->getUri());
    }

    /**
     * Redirects to the exact provided location and sets the response to allow
     * bypassing external redirect protection.
     *
     * NEVER use this method with a user-provided location.
     */
    public function externalRedirect(string $location, int $status_code = 302): RedirectResponse
    {
        return $this->response_factory
            ->redirect($location, $status_code)
            ->withExternalRedirectAllowed();
    }

    /**
     * Redirects to the value of the referer header or the fallback location if
     * no referer header is present.
     */
    public function redirectBack(string $fallback = '/'): RedirectResponse
    {
        $referer = $this->current_request->getHeaderLine('referer');
        $location = empty($referer) ? $fallback : $referer;

        return $this->redirectTo($location);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function view(string $view_identifier, array $data = []): ViewResponse
    {
        $data = array_replace([
            'request' => $this->current_request,
        ], $data);

        return $this->response_factory->view($view_identifier, $data);
    }

    public function html(string $html): Response
    {
        return $this->response_factory->html($html);
    }

    /**
     * @param mixed $data
     *
     * @throws JsonException
     */
    public function json($data, int $status_code = 200, int $options = 0, int $depth = 512): Response
    {
        return $this->response_factory->json($data, $status_code, $options, $depth);
    }
}
