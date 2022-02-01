<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestResponse;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;

class MiddlewareTestCaseTest extends MiddlewareTestCase
{

    use CreateTestPsr17Factories;
    use CreateTestPsrContainer;

    /**
     * @test
     */
    public function testResponseIsTestResponse(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respond()->html('foo');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);
        $response->psr()->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assertNextWasCalled_can_pass(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);

        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function assertNextWasCalled_can_fail(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respond()->html('foo');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);

        try {
            $response->assertNextMiddlewareCalled();
            $this->fail('Test assertion gave false positive outcome.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith('The next middleware was not called.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function assertNextWasNotCalled_can_pass(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respond()->html('foo');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);

        $response->assertNextMiddlewareNotCalled();
    }

    /**
     * @test
     */
    public function assertNextWasNotCalled_can_fail(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        try {
            $response->assertNextMiddlewareNotCalled();
            $this->fail('Test assertion gave false positive outcome.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith('The next middleware was called.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function assertions_about_the_passed_request_to_the_next_middleware_can_be_made(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request->withAttribute('foo', 'bar'));
            }

        };

        $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertSame('bar', $this->getReceivedRequest()->getAttribute('foo'));
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_middleware_did_not_delegate_to_the_next_one_and_assertions_about_the_received_request_are_made(
    ): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respond()->html('foo');
            }

        };

        $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        try {
            $this->assertSame('bar', $this->getReceivedRequest()->getAttribute('foo'));
            $this->fail('Test assertions gave false result.');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('The next middleware was not called.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function delegating_to_the_next_middleware_and_using_its_response_works(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $response = $next($request);
                return $response->withHeader('foo', 'bar');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResponse::class, $response);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertHeader('foo', 'bar');
    }

    /**
     * @test
     */
    public function custom_responses_for_the_next_middleware_can_be_set(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->html('foo');
        });

        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->psr()->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assertNextMiddlewareCalled_still_works_with_custom_responses(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->html('foo');
        });

        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assertNextMiddlewareCalled_works_if_the_middleware_under_test_generated_a_custom_responses(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $next($request);

                return $this->respond()->html('foo');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function everything_is_reset_after_running_a_middleware(): void
    {
        $middleware = new class extends AbstractMiddleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                if ($request->isGet()) {
                    $next($request);
                }
                return $this->respond()->html('foo');
            }

        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertSeeText('foo');

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo', [], 'POST'));
        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertSeeText('foo');
    }

}