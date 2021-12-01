<?php

declare(strict_types=1);

namespace Snicco\Testing\Assertable;

use PHPUnit\Framework\Assert;
use Snicco\Http\Psr7\Response;
use Snicco\Testing\TestResponse;

class MiddlewareTestResponse extends TestResponse
{
    
    private bool $next_middleware_called;
    
    public function __construct(Response $response, bool $next_called = false)
    {
        $this->next_middleware_called = $next_called;
        parent::__construct($response);
    }
    
    public function assertNextMiddlewareCalled() :MiddlewareTestResponse
    {
        Assert::assertTrue($this->next_middleware_called, "The next middleware was not called.");
        return $this;
    }
    
    public function assertNextMiddlewareNotCalled() :MiddlewareTestResponse
    {
        Assert::assertFalse($this->next_middleware_called, 'The next middleware was called.');
        return $this;
    }
    
}