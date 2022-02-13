<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\Payload\JsonToArray;
use stdClass;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class JsonToArrayTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function read_verbs_are_not_processed(): void
    {
        $request = $this->jsonRequest();
        $request->getBody()->write('{"bar":"foo"}');

        $response = $this->runMiddleware(new JsonToArray(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->receivedRequest()->getParsedBody());

        $request = $this->jsonRequest('POST');
        $request->getBody()->write('{"bar":"foo"}');

        $response = $this->runMiddleware(new JsonToArray(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(['bar' => 'foo'], $this->receivedRequest()->getParsedBody());
    }

    /**
     * @test
     */
    public function nothing_is_processed_with_different_content_type_header(): void
    {
        $request = $this->jsonRequest()
            ->withMethod('POST')
            ->withHeader('content-type', 'text/html');

        $response = $this->runMiddleware(new JsonToArray(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(null, $this->receivedRequest()->getParsedBody());
    }

    /**
     * @test
     */
    public function empty_json_input_returns_an_array(): void
    {
        $request = $this->jsonRequest()
            ->withMethod('POST');

        $response = $this->runMiddleware(new JsonToArray(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame([], $this->receivedRequest()->getParsedBody());
    }

    /**
     * @test
     */
    public function json_exceptions_are_caught_and_transformed(): void
    {
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write('{"bar":"foo",}');

        try {
            $this->runMiddleware(new JsonToArray(), $request);
            $this->fail('Invalid Json did not throw exception');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statusCode());
            $this->assertStringStartsWith(
                'Cant decode json body',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function objects_are_returned_as_array(): void
    {
        $std = new stdClass();
        $std->foo = 'bar';
        $json = json_encode($std, JSON_THROW_ON_ERROR);

        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write($json);

        $response = $this->runMiddleware(new JsonToArray(), $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame(['foo' => 'bar'], $this->receivedRequest()->getParsedBody());
    }

    /**
     * @test
     */
    public function non_arrays_thrown_an_exception(): void
    {
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write(json_encode('foo', JSON_THROW_ON_ERROR));

        try {
            $this->runMiddleware(new JsonToArray(), $request);
            $this->fail('Invalid Json did not throw exception');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statusCode());
            $this->assertStringContainsString(
                'json_decoding the request body did not return an array.',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function non_string_keys_at_the_root_level_will_throw_an_exception(): void
    {
        $request = $this->jsonRequest()->withMethod('POST');
        $request->getBody()->write(json_encode(['foobar'], JSON_THROW_ON_ERROR));

        try {
            $this->runMiddleware(new JsonToArray(), $request);
            $this->fail('Invalid Json did not throw exception');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statusCode());
            $this->assertStringContainsString(
                'json_decoding the request body must return an array keyed by strings.',
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