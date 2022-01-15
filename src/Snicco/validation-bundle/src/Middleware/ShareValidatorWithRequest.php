<?php

declare(strict_types=1);

namespace Snicco\Validation\Middleware;

use Snicco\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

class ShareValidatorWithRequest extends AbstractMiddleware
{
    
    private Validator $validator;
    
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        return $next($request->withValidator($this->validator));
    }
    
}