<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use InvalidArgumentException;
use JsonSerializable;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Psr\Http\Message\StreamInterface as Psr7Stream;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\Responsable;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use stdClass;
use Webmozart\Assert\Assert;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @interal You should never depend on this concrete response factory implementation.
 *          Always depend on the {@see ResponseFactory} interface
 */
final class DefaultResponseFactory implements ResponseFactory, Redirector
{

    private Psr17ResponseFactory $psr_response;

    private Psr17StreamFactory $psr_stream;

    private UrlGenerator $url;

    public function __construct(Psr17ResponseFactory $response, Psr17StreamFactory $stream, UrlGenerator $url)
    {
        $this->psr_response = $response;
        $this->psr_stream = $stream;
        $this->url = $url;
    }

    public function delegate(bool $should_headers_be_sent = true): DelegatedResponse
    {
        return new DelegatedResponse($should_headers_be_sent, $this->createResponse());
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): Psr7Response
    {
        return $this->make($code, $reasonPhrase);
    }

    public function make(int $status_code = 200, string $reason_phrase = ''): Response
    {
        Assert::range($status_code, 100, 599);

        $psr_response = $this->psr_response->createResponse($status_code, $reason_phrase);

        return new Response($psr_response);
    }

    public function noContent(): Response
    {
        return $this->make(204);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): Psr7Stream
    {
        return $this->psr_stream->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): Psr7Stream
    {
        return $this->psr_stream->createStreamFromResource($resource);
    }

    public function toResponse($response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof Psr7Response) {
            return new Response($response);
        }

        if (is_string($response)) {
            return $this->html($response);
        }

        if (is_array($response) || $response instanceof JsonSerializable
            || $response
            instanceof
            stdClass) {
            return $this->json($response);
        }

        if ($response instanceof Responsable) {
            return $this->toResponse(
                $response->toResponsable()
            );
        }

        throw new InvalidArgumentException('Invalid response returned by a route.');
    }

    public function html(string $html, int $status_code = 200): Response
    {
        return $this->make($status_code)
            ->html($this->psr_stream->createStream($html));
    }

    public function createStream(string $content = ''): Psr7Stream
    {
        return $this->psr_stream->createStream($content);
    }

    /** @todo This should use a dedicated json response class. */
    public function json($content, int $status_code = 200): Response
    {
        return $this->make($status_code)
            ->json($this->createStream(json_encode($content, JSON_THROW_ON_ERROR)));
    }

    public function home(array $arguments = [], int $status_code = 302): RedirectResponse
    {
        try {
            $location = $this->url->toRoute('home', $arguments);
        } catch (RouteNotFound $exception) {
            $location = $this->url->to('/', $arguments);
        }

        return $this->redirect($location, $status_code);
    }

    public function redirect(string $location, int $status_code = 302): RedirectResponse
    {
        $psr = $this->make($status_code);
        return (new RedirectResponse($psr))->to($location);
    }

    public function toRoute(string $name, array $arguments = [], int $status_code = 302): RedirectResponse
    {
        return $this->redirect(
            $this->url->toRoute($name, $arguments),
            $status_code
        );
    }

    public function refresh(): RedirectResponse
    {
        return $this->redirect($this->url->full());
    }

    public function back(string $fallback = '/', int $status_code = 302): RedirectResponse
    {
        return $this->redirect(
            $this->url->previous($fallback),
            $status_code
        );
    }

    public function deny(string $path, int $status_code = 302, array $query = []): RedirectResponse
    {
        Assert::keyNotExists($query, 'intended');
        $current = $this->url->full();

        $location = $this->url->to($path, array_merge($query, ['intended' => $current]));

        return $this->redirect($location, $status_code);
    }

    public function intended(string $fallback = '/', int $status_code = 302): RedirectResponse
    {
        $current = $this->url->full();
        parse_str(parse_url($current, PHP_URL_QUERY) ?? '', $query);
        $query = (array)$query;

        $location = $query['intended'] ?? $this->url->to($fallback);

        return $this->redirect($location, $status_code);
    }

    public function to(string $path, int $status_code = 302, array $query = []): RedirectResponse
    {
        return $this->redirect(
            $this->url->to($path, $query),
            $status_code
        );
    }

    public function secure(string $path, int $status_code = 302, array $query = []): RedirectResponse
    {
        return $this->redirect(
            $this->url->secure($path, $query),
            $status_code
        );
    }

    public function away(string $absolute_url, int $status_code = 302): RedirectResponse
    {
        $res = $this->redirect($absolute_url, $status_code);
        return $res->withExternalRedirectAllowed();
    }

}