<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use InvalidArgumentException;
use JsonException;
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

use function is_string;
use function json_encode;
use function parse_url;

use const JSON_THROW_ON_ERROR;
use const PHP_URL_QUERY;

final class ResponseFactory implements Redirector, Psr17ResponseFactory, Psr17StreamFactory
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

    public function createResponse(int $code = 200, string $reasonPhrase = ''): Response
    {
        Assert::range($code, 100, 599);
        $psr_response = $this->psr_response->createResponse($code, $reasonPhrase);
        return new Response($psr_response);
    }

    public function noContent(): Response
    {
        return $this->createResponse(204);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): Psr7Stream
    {
        return $this->psr_stream->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): Psr7Stream
    {
        return $this->psr_stream->createStreamFromResource($resource);
    }

    public function createStream(string $content = ''): Psr7Stream
    {
        return $this->psr_stream->createStream($content);
    }

    /**
     * @param string|array|Response|Psr7Response|stdClass|JsonSerializable|Responsable $response
     * @throws JsonException
     */
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
        return $this->createResponse($status_code)
            ->withHtml($this->psr_stream->createStream($html));
    }

    /**
     * @param mixed $data
     * @throws JsonException
     */
    public function json($data, int $status_code = 200, int $options = JSON_THROW_ON_ERROR, int $depth = 512): Response
    {
        $stream = json_encode($data, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE || false === $stream) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        return $this->createResponse($status_code)->withJson(
            $this->createStream($stream)
        );
    }

    public function redirect(string $location, int $status_code = 302): RedirectResponse
    {
        $psr = $this->createResponse($status_code);
        return (new RedirectResponse($psr))->to($location);
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
        $query = parse_url($current, PHP_URL_QUERY);

        if (!$query) {
            $query = '';
        }

        parse_str($query, $query);

        if (isset($query['intended']) && is_string($query['intended'])) {
            $location = $query['intended'];
        } else {
            $location = $this->url->to($fallback);
        }

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