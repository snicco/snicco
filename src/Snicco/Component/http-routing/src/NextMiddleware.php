<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function call_user_func;

final class NextMiddleware implements RequestHandlerInterface, MiddlewareInterface
{

    /**
     * @var callable(Request):PsrResponse
     */
    private $callback;

    /**
     * @param callable(Request):PsrResponse $callback
     * @psalm-param  callable(Request=):PsrResponse $callback // Request is optional
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function process(PsrRequest $request, RequestHandlerInterface $handler): PsrResponse
    {
        return $this->delegate($request);
    }

    public function __invoke(PsrRequest $request): Response
    {
        return $this->delegate($request);
    }

    public function handle(PsrRequest $request): PsrResponse
    {
        return $this->delegate($request);
    }

    private function delegate(PsrRequest $request): Response
    {
        $psr_response = call_user_func(
            $this->callback,
            Request::fromPsr($request)
        );

        return Response::fromPsr($psr_response);
    }

}