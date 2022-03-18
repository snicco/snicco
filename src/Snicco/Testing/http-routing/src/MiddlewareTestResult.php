<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

final class MiddlewareTestResult
{
    private bool $next_middleware_called;

    private AssertableResponse $response;

    public function __construct(ResponseInterface $response, bool $next_called = false)
    {
        $this->next_middleware_called = $next_called;
        $response = $response instanceof Response ? $response : new Response($response);
        $this->response = new AssertableResponse($response);
    }

    public function assertNextMiddlewareCalled(): void
    {
        PHPUnit::assertTrue($this->next_middleware_called, 'The next middleware was not called.');
    }

    public function assertNextMiddlewareNotCalled(): void
    {
        PHPUnit::assertFalse($this->next_middleware_called, 'The next middleware was called.');
    }

    public function assertableResponse(): AssertableResponse
    {
        return $this->response;
    }
}
