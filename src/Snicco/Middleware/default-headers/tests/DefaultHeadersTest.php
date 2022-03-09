<?php

declare(strict_types=1);

namespace Snicco\Middleware\DefaultHeaders\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\DefaultHeaders\DefaultHeaders;

class DefaultHeadersTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function all_headers_are_added_to_the_response(): void
    {
        $response = $this->runMiddleware(
            new DefaultHeaders([
                'foo' => 'bar',
                'baz' => 'biz',
            ]),
            $this->frontendRequest()
        );

        $response->assertNextMiddlewareCalled();

        $response->assertableResponse()->assertHeader('foo', 'bar');
        $response->assertableResponse()->assertHeader('baz', 'biz');
    }

    /**
     * @test
     */
    public function header_values_are_not_overwritten(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withHeader('foo', 'bar');
        });

        $response = $this->runMiddleware(
            new DefaultHeaders([
                'foo' => 'baz',
            ]),
            $this->frontendRequest()
        );
        $response->assertNextMiddlewareCalled();

        $response->assertableResponse()->assertHeader('foo', 'bar');
    }
}
