<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use LogicException;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestResult;

/**
 * @internal
 */
final class MiddlewareTestCaseTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function test_response_is_test_response(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResult::class, $response);
        $response->assertableResponse()
            ->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assert_next_was_called_can_pass(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResult::class, $response);

        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function assert_next_was_called_can_fail(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResult::class, $response);

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
    public function assert_next_was_not_called_can_pass(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResult::class, $response);

        $response->assertNextMiddlewareNotCalled();
    }

    /**
     * @test
     */
    public function assert_next_was_not_called_can_fail(): void
    {
        $middleware = new class() extends Middleware {
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
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request->withAttribute('foo', 'bar'));
            }
        };

        $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertSame('bar', $this->receivedRequest()->getAttribute('foo'));
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_middleware_did_not_delegate_to_the_next_one_and_assertions_about_the_received_request_are_made(
        ): void {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        try {
            $this->assertSame('bar', $this->receivedRequest()->getAttribute('foo'));
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
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $response = $next($request);

                return $response->withHeader('foo', 'bar');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $this->assertInstanceOf(MiddlewareTestResult::class, $response);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertHeader('foo', 'bar');
    }

    /**
     * @test
     */
    public function custom_responses_for_the_next_middleware_can_be_set(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()
                ->html('foo');
        });

        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertableResponse()
            ->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assert_next_middleware_called_still_works_with_custom_responses(): void
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->responseFactory()
                ->html('foo');
        });

        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function assert_next_middleware_called_works_if_the_middleware_under_test_generated_a_custom_responses(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $next($request);

                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function everything_is_reset_after_running_a_middleware(): void
    {
        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                if ($request->isGet()) {
                    $next($request);
                }

                return $this->responseFactory()
                    ->html('foo');
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertSeeText('foo');

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo', [], 'POST'));
        $response->assertNextMiddlewareNotCalled();
        $response->assertableResponse()
            ->assertSeeText('foo');
    }

    /**
     * @test
     */
    public function test_exception_if_response_utils_is_access_to_early(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('response utils');
        $this->responseUtils();
    }

    /**
     * @test
     */
    public function test_psr_factories(): void
    {
        $this->assertInstanceOf(ResponseFactoryInterface::class, $this->psrResponseFactory());
        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $this->psrServerRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $this->psrStreamFactory());
        $this->assertInstanceOf(UriFactoryInterface::class, $this->psrUriFactory());
    }

    /**
     * @test
     */
    public function test_with_routes(): void
    {
        $this->withRoutes([Route::create('/foo', Route::DELEGATE, 'r1')]);

        $this->withNextMiddlewareResponse(function () {
            return $this->responseUtils()
                ->redirectToRoute('r1');
        });

        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertRedirect('/foo');
    }

    /**
     * @test
     */
    public function test_response_instance_is_not_changed(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return new ViewResponse('foo', $response);
        });

        $middleware = new class() extends Middleware {
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $result = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $result->assertNextMiddlewareCalled();
        $this->assertInstanceOf(ViewResponse::class, $result->assertableResponse()->getPsrResponse());
    }
}
