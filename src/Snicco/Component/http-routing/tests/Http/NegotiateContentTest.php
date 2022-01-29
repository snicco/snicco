<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\NegotiateContent;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

final class NegotiateContentTest extends MiddlewareTestCase
{
    
    /** @test */
    public function content_negotiation_is_performed()
    {
        $this->withNextMiddlewareResponse(function (Response $response, Request $request) {
            $response->getBody()->write($request->getHeaderLine('accept'));
            return $response;
        });
        
        $middleware = new NegotiateContent(['en']);
        
        $request = $this->frontendRequest()->withHeader(
            'accept',
            'text/html;q=0.9, application/json;q=0.8'
        );
        
        $response = $this->runMiddleware($middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertSeeText('text/html');
        $response->psr()->assertHeader('content-type', 'text/html; charset=UTF-8');
        
        $request = $this->frontendRequest()->withHeader(
            'accept',
            'text/html;q=0.8, application/json;q=0.9'
        );
        
        $response = $this->runMiddleware($middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertSeeText('application/json');
        $response->psr()->assertHeader('content-type', 'application/json; charset=UTF-8');
        $response->psr()->assertHeader('content-language', 'en');
    }
    
    /** @test */
    public function an_exception_is_thrown_if_no_content_type_can_be_matched()
    {
        $middleware = new NegotiateContent(['en']);
        
        $request = $this->frontendRequest()->withHeader(
            'accept',
            'application/rdf+xml'
        );
        
        try {
            $response = $this->runMiddleware($middleware, $request);
            $this->fail("No exception thrown for bad content type");
        } catch (HttpException $e) {
            $this->assertSame(406, $e->statusCode());
            $this->assertStringStartsWith('Failed content negotiation', $e->getMessage());
        }
    }
    
}