<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Snicco\Http\Psr7\Request;
use Tests\Core\MiddlewareTestCase;
use Snicco\Middleware\JsonPayload;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class JsonPayloadTest extends MiddlewareTestCase
{
    
    /** @test */
    public function read_verbs_are_not_processed()
    {
        $request = $this->jsonRequest();
        $request->getBody()->write('{"bar":"foo"}');
        
        $response = $this->runMiddleware(new JsonPayload(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->receivedRequest()->getParsedBody());
        
        $request = $this->jsonRequest('POST');
        $request->getBody()->write('{"bar":"foo"}');
        
        $response = $this->runMiddleware(new JsonPayload(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame(['bar' => 'foo'], $this->receivedRequest()->getParsedBody());
    }
    
    /** @test */
    public function nothing_is_processed_with_missing_content_type_header()
    {
        $request = $this->jsonRequest()
                        ->withMethod('POST')
                        ->withHeader('Content-Type', 'text/html');
        
        $response = $this->runMiddleware(new JsonPayload(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->receivedRequest()->getParsedBody());
    }
    
    /** @test */
    public function empty_json_input_returns_an_array()
    {
        $request = $this->jsonRequest()
                        ->withMethod('POST');
        
        $response = $this->runMiddleware(new JsonPayload(), $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame([], $this->receivedRequest()->getParsedBody());
    }
    
    /** @test */
    public function json_exceptions_are_caught_and_transformed()
    {
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write('{"bar":"foo",}');
        
        try {
            $this->runMiddleware(new JsonPayload(), $request);
            $this->fail('Invalid Json did not throw exception');
        } catch (HttpException $e) {
            $this->assertSame(500, $e->httpStatusCode());
            $this->assertSame(
                'JSON: Syntax error. Payload: {"bar":"foo",}.',
                $e->getMessage()
            );
        }
    }
    
    private function jsonRequest(string $method = 'GET') :Request
    {
        return $this->frontendRequest($method, 'foo')
                    ->withAddedHeader('Content-Type', 'application/json');
    }
    
}