<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Tests\UnitTest;
use Snicco\Http\Delegate;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Snicco\Middleware\JsonPayload;
use Tests\helpers\AssertsResponse;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreatePsr17Factories;
use Tests\helpers\CreateRouteCollection;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class JsonPayloadTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use AssertsResponse;
    
    private Request  $request;
    private Delegate $route_action;
    
    /** @test */
    public function read_verbs_are_not_processed()
    {
        
        $this->request->getBody()->write('{"bar":"foo"}');
        $this->middleware()->handle($this->request, $this->route_action);
        $this->assertParsedBody(null);
        
        $request = $this->jsonRequest()->withMethod('HEAD');
        $request->getBody()->write('{"bar":"foo"}');
        $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody(null);
        
        $request = $this->jsonRequest()->withMethod('OPTIONS');
        $request->getBody()->write('{"bar":"foo"}');
        $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody(null);
        
        $request = $this->jsonRequest()->withMethod('TRACE');
        $request->getBody()->write('{"bar":"foo"}');
        $response = $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody(null);
        
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write('{"bar":"foo"}');
        $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody(['bar' => 'foo']);
        
    }
    
    private function middleware() :JsonPayload
    {
        
        return new JsonPayload();
        
    }
    
    private function assertParsedBody($expected)
    {
        
        $this->assertSame($expected, $GLOBALS['test']['parsed_json']);
        
    }
    
    private function jsonRequest()
    {
        
        return TestRequest::from('GET', 'foo')
                          ->withAddedHeader('Content-Type', 'application/json');
        
    }
    
    /** @test */
    public function nothing_is_processed_with_missing_content_type_header()
    {
        
        $request = $this->jsonRequest()
                        ->withMethod('POST')
                        ->withHeader('Content-Type', 'text/html');
        
        $request->getBody()->write('{"bar":"foo"}');
        $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody(null);
        
    }
    
    /** @test */
    public function empty_json_input_returns_an_array()
    {
        
        $request = $this->jsonRequest()
                        ->withMethod('POST');
        
        $request->getBody()->write('');
        $this->middleware()->handle($request, $this->route_action);
        $this->assertParsedBody([]);
        
    }
    
    /** @test */
    public function json_exceptions_are_caught_and_transformed()
    {
        
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write('{"bar":"foo",}');
        
        try {
            
            $this->middleware()->handle($request, $this->route_action);
            $this->fail('Invalid Json did not throw exception');
            
        } catch (HttpException $e) {
            
            $this->assertSame(500, $e->httpStatusCode());
            $this->assertSame(
                'Failed to convert the request payload to JSON.',
                $e->getMessage()
            );
            
        }
        
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        $this->request = TestRequest::from('GET', 'foo')
                                    ->withAddedHeader('Content-Type', 'application/json');
        
        $GLOBALS['test']['parsed_json'] = '';
        
        $response = $this->createResponseFactory();
        
        $this->route_action = new Delegate(function (Request $request) use ($response) {
            
            $parsed = $request->getParsedBody();
            
            $GLOBALS['test']['parsed_json'] = $parsed;
            
            return $response->createResponse();
            
        });
        
    }
    
}