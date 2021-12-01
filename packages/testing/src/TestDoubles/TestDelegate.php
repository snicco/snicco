<?php

declare(strict_types=1);

namespace Snicco\Testing\TestDoubles;

use Closure;
use Snicco\Http\Psr7\Request;
use Snicco\Testing\Assertable;
use Snicco\Http\ResponseFactory;

class TestDelegate
{
    
    private ResponseFactory $response_factory;
    private Closure         $next_response;
    
    public function __construct(ResponseFactory $response_factory, Closure $next_response)
    {
        $this->response_factory = $response_factory;
        $this->next_response = $next_response;
    }
    
    public function __invoke(Request $request)
    {
        $GLOBALS['test']['_next_middleware_called'] = true;
        
        $testable_response = new Assertable\MiddlewareTestResponse(
            $this->response_factory->createResponse(),
            true
        );
        
        $res = call_user_func(
            $this->next_response,
            $this->response_factory->createResponse(),
            $request
        );
        $res->test_response = $testable_response;
        $res->received_request = $request;
        return $res;
    }
    
}