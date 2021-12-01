<?php

namespace Snicco\Validation;

use Snicco\Support\Arr;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Validation\Exceptions\ValidationException;

class ValidationExceptionRenderer
{
    
    private ResponseFactory $response_factory;
    private array           $dont_flash;
    
    public function __construct(ResponseFactory $response_factory, array $dont_flash)
    {
        $this->response_factory = $response_factory;
        $this->dont_flash = $dont_flash;
    }
    
    public function render(ValidationException $exception, Request $request)
    {
        if ($request->isExpectingJson()) {
            return $this->response_factory->json([
                
                'message' => $exception->getJsonMessage(),
                'errors' => $exception->errorsAsArray(),
            
            ], $exception->httpStatusCode());
        }
        
        $response = $this->response_factory->redirect()->previous();
        
        // It's possible to use the validation extension without the session extension.
        if ( ! $response->hasSession()) {
            return $response;
        }
        
        return $response->withErrors($exception->messages(), $exception->namedBag())
                        ->withInput(Arr::except($request->input(), $this->dont_flash));
    }
    
}