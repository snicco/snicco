<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use LogicException;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;

/**
 * @internal
 */
final class MiddlewareTest extends TestCase
{
    use CreateTestPsr17Factories;
    use CreatesPsrRequests;
    use CreateUrlGenerator;

    private \Pimple\Psr11\Container $pimple_psr;

    private Container $pimple;

    protected function setUp(): void
    {
        parent::setUp();
        $pimple = new Container();
        $this->pimple = $pimple;

        $this->pimple_psr = new \Pimple\Psr11\Container($pimple);
        $pimple[ResponseFactory::class] = fn (): ResponseFactory => $this->createResponseFactory();
        $pimple[UrlGenerator::class] = fn (): UrlGenerator => $this->createUrlGenerator();
    }

    /**
     * @test
     */
    public function middleware_has_access_to_the_url_generator(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $url = $this->url()
                    ->to('/foo');

                return $this->responseFactory()
                    ->html($url);
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $response = $middleware->process($this->frontendRequest(), $this->getNext());

        $this->assertSame('/foo', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function middleware_has_access_to_the_response_utils(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respondWith()
                    ->redirectTo('/foo', 303);
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $response = $middleware->process($this->frontendRequest(), $this->getNext());

        $this->assertSame('/foo', $response->getHeaderLine('location'));
        $this->assertSame(303, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_process_can_be_called_with_normal_psr_interface(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $rf = $this->createResponseFactory();

        $response = $middleware->process(
            $this->psrServerRequestFactory()
                ->createServerRequest('GET', '/foo'),
            new class($rf) implements RequestHandlerInterface {
                private ResponseFactory $factory;

                public function __construct(ResponseFactory $factory)
                {
                    $this->factory = $factory;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->html('foo');
                }
            }
        );

        $this->assertSame('foo', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function the_current_request_is_used_for_the_response_utils(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respondWith()
                    ->refresh();
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $response = $middleware->process($this->frontendRequest('https://foo.com/bar'), $this->getNext());
        $this->assertSame('https://foo.com/bar', $response->getHeaderLine('location'));

        $response = $middleware->process($this->frontendRequest('https://foo.com/baz'), $this->getNext());
        $this->assertSame('https://foo.com/baz', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_next_middleware_can_be_called_with_handle_method(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next->handle($request);
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $rf = $this->createResponseFactory();

        $response = $middleware->process(
            $this->psrServerRequestFactory()
                ->createServerRequest('GET', '/foo'),
            new class($rf) implements RequestHandlerInterface {
                private ResponseFactory $factory;

                public function __construct(ResponseFactory $factory)
                {
                    $this->factory = $factory;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->html('foo');
                }
            }
        );

        $this->assertSame('foo', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function test_exception_if_response_factory_not_bound(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $url = $this->url()
                    ->to('/foo');

                return $this->responseFactory()
                    ->html($url);
            }
        };

        unset($this->pimple[ResponseFactory::class]);
        $middleware->setContainer($this->pimple_psr);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The ResponseFactory is not bound correctly');

        $middleware->process($this->frontendRequest(), $this->getNext());
    }

    /**
     * @test
     */
    public function test_exception_if_url_generator_not_bound(): void
    {
        $middleware = new class() extends Middleware {
            protected function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                $url = $this->url()
                    ->to('/foo');

                return $this->responseFactory()
                    ->html($url);
            }
        };

        unset($this->pimple[UrlGenerator::class]);
        $middleware->setContainer($this->pimple_psr);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The UrlGenerator is not bound correctly');

        $middleware->process($this->frontendRequest(), $this->getNext());
    }

    /**
     * @test
     */
    public function test_exception_if_current_request_is_not_set(): void
    {
        $middleware = new class() extends Middleware {
            /**
             * Handle method is made public on purpose.
             *
             * @noRector
             */
            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respondWith()
                    ->refresh();
            }
        };
        $middleware->setContainer($this->pimple_psr);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('current request not set on middleware');

        $middleware->handle(
            Request::fromPsr($this->psrServerRequestFactory()->createServerRequest('GET', '/foo')),
            $this->getNext()
        );
    }

    private function getNext(): NextMiddleware
    {
        return new NextMiddleware(function (): void {
            throw new RuntimeException('Next should not be called.');
        });
    }
}
