<?php

namespace Snicco\Component\Core\ExceptionHandling\Exceptions;

use Throwable;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\HttpRouting\Http\ResponseFactory;

class ErrorViewException extends HttpException
{
    
    public function __construct(string $log_message, Throwable $previous = null)
    {
        parent::__construct(
            500,
            $log_message,
            $previous
        );
    }
    
    public function render(ResponseFactory $response_factory, Request $request) :Response
    {
        return $request->isExpectingJson()
            ? $response_factory->json(['message' => 'Server Error'], 500)
            : $response_factory->html('<h1> Server Error </h1>')->withStatus(500);
    }
    
}