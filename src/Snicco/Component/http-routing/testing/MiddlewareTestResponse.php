<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @api
 */
final class MiddlewareTestResponse
{
    
    private bool               $next_middleware_called;
    private AssertableResponse $response;
    
    public function __construct(ResponseInterface $response, bool $next_called = false)
    {
        $this->next_middleware_called = $next_called;
        $this->response = new AssertableResponse(new Response($response));
    }
    
    public function assertNextMiddlewareCalled() :MiddlewareTestResponse
    {
        PHPUnit::assertTrue($this->next_middleware_called, 'The next middleware was not called.');
        return $this;
    }
    
    public function assertNextMiddlewareNotCalled() :MiddlewareTestResponse
    {
        PHPUnit::assertFalse($this->next_middleware_called, 'The next middleware was called.');
        return $this;
    }
    
    public function psr() :AssertableResponse
    {
        return $this->response;
    }
    
}