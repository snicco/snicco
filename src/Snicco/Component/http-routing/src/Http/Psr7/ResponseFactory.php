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
use Snicco\Component\HttpRouting\Http\Responsable;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use stdClass;
use Webmozart\Assert\Assert;

use function array_keys;
use function is_array;
use function is_string;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ResponseFactory implements Psr17ResponseFactory, Psr17StreamFactory
{
    private Psr17ResponseFactory $psr_response;

    private Psr17StreamFactory $psr_stream;

    public function __construct(Psr17ResponseFactory $response, Psr17StreamFactory $stream)
    {
        $this->psr_response = $response;
        $this->psr_stream = $stream;
    }

    public function delegate(bool $should_headers_be_sent = true): DelegatedResponse
    {
        $response = new DelegatedResponse($this->createResponse());
        if (! $should_headers_be_sent) {
            $response = $response->withoutSendingHeaders();
        }

        return $response;
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
     * @param array|JsonSerializable|Psr7Response|Responsable|Response|stdClass|string $response
     *
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
            return $this->toResponse($response->toResponsable());
        }

        throw new InvalidArgumentException('Invalid response returned by a route.');
    }

    public function view(string $view, array $data): ViewResponse
    {
        Assert::allString(array_keys($data));

        $response = new ViewResponse($view, $this->createResponse());

        /**
         * @psalm-var array<string,mixed> $data
         */
        return $response->withViewData($data);
    }

    public function html(string $html, int $status_code = 200): Response
    {
        return $this->createResponse($status_code)
            ->withHtml($this->psr_stream->createStream($html));
    }

    /**
     * @param mixed $data
     *
     * @throws JsonException
     */
    public function json($data, int $status_code = 200, int $options = 0, int $depth = 512): Response
    {
        /** @var string $stream */
        $stream = json_encode($data, $options | JSON_THROW_ON_ERROR, $depth);

        return $this->createResponse($status_code)
            ->withJson($this->createStream($stream));
    }

    public function redirect(string $location, int $status_code = 302): RedirectResponse
    {
        $psr = $this->createResponse($status_code);

        return (new RedirectResponse($psr))->to($location);
    }
}
