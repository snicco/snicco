<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\Payload\JsonPayload;

class JsonPayloadTest extends MiddlewareTestCase
{

    /** @test */
    public function read_verbs_are_not_processed()
    {
        $request = $this->jsonRequest();
        $request->getBody()->write('{"bar":"foo"}');

        $response = $this->runMiddleware(new JsonPayload(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->getReceivedRequest()->getParsedBody());

        $request = $this->jsonRequest('POST');
        $request->getBody()->write('{"bar":"foo"}');

        $response = $this->runMiddleware(new JsonPayload(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(['bar' => 'foo'], $this->getReceivedRequest()->getParsedBody());
    }

    /** @test */
    public function nothing_is_processed_with_different_content_type_header()
    {
        $request = $this->jsonRequest()
            ->withMethod('POST')
            ->withHeader('content-type', 'text/html');

        $response = $this->runMiddleware(new JsonPayload(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->getReceivedRequest()->getParsedBody());
    }

    /** @test */
    public function empty_json_input_returns_an_array()
    {
        $request = $this->jsonRequest()
            ->withMethod('POST');

        $response = $this->runMiddleware(new JsonPayload(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame([], $this->getReceivedRequest()->getParsedBody());
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
            $this->assertSame(400, $e->statusCode());
            $this->assertStringStartsWith(
                'Cant decode json body',
                $e->getMessage()
            );
        }
    }

    private function jsonRequest(string $method = 'GET'): Request
    {
        return $this->frontendRequest('/foo', [], $method)
            ->withAddedHeader('Content-Type', 'application/json');
    }

}