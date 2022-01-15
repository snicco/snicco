<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Assertable;

use Snicco\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

class MiddlewareTestResponse extends TestResponse
{
    
    private bool $next_middleware_called;
    
    public function __construct(ResponseInterface $response, bool $next_called = false)
    {
        $this->next_middleware_called = $next_called;
        parent::__construct(new Response($response));
    }
    
    public function assertNextMiddlewareCalled() :MiddlewareTestResponse
    {
        PHPUnit::assertTrue($this->next_middleware_called, "The next middleware was not called.");
        return $this;
    }
    
    public function assertNextMiddlewareNotCalled() :MiddlewareTestResponse
    {
        PHPUnit::assertFalse($this->next_middleware_called, 'The next middleware was called.');
        return $this;
    }
    
}