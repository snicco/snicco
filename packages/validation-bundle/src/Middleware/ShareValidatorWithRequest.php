<?php

declare(strict_types=1);

namespace Snicco\Validation\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Snicco\Validation\Validator;
use Psr\Http\Message\ResponseInterface;

class ShareValidatorWithRequest extends Middleware
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