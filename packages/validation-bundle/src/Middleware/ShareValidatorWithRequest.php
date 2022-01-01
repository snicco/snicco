<?php

declare(strict_types=1);

namespace Snicco\Validation\Middleware;

use Snicco\Validation\Validator;
use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;

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