<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use Closure;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\HttpRouting\LazyHttpErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Throwable;

final class LazyErrorHandlerTest extends TestCase
{

    use CreateTestPsrContainer;
    use CreateTestPsr17Factories;

    /**
     * @test
     */
    public function the_lazy_error_handler_behaves_the_same_as_the_real_error_handler_it_proxies_to(): void
    {
        $c = $this->createContainer();
        $c[HttpErrorHandlerInterface::class] = new TestableErrorHandler(function () {
        });
        $lazy_handler = new LazyHttpErrorHandler($c);

        $this->assertInstanceOf(HttpErrorHandlerInterface::class, $lazy_handler);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_lazy_error_handler_doesnt_have_the_http_error_handler_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The psr container needs a service for id [' . HttpErrorHandlerInterface::class . '].'
        );

        new LazyHttpErrorHandler($c = $this->createContainer());
    }

    /**
     * @test
     */
    public function calls_are_proxies_to_the_real_handler(): void
    {
        $count = 0;
        $c = $this->createContainer();

        $real_handler =
            new TestableErrorHandler(function (Throwable $e, ServerRequestInterface $request) {
                $response = $this->psrResponseFactory()->createResponse(500);
                $response->getBody()->write('foo error');
                return $response;
            });

        $c->singleton(HttpErrorHandlerInterface::class, function () use (&$count, $real_handler) {
            $count++;
            return $real_handler;
        });

        $lazy_handler = new LazyHttpErrorHandler($c);

        $response = $lazy_handler->handle(
            new Exception('secret stuff'),
            $this->psrServerRequestFactory()->createServerRequest('GET', '/foo')
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('foo error', (string)$response->getBody());
    }

}

class TestableErrorHandler implements HttpErrorHandlerInterface
{

    private Closure $return;

    public function __construct(Closure $return)
    {
        $this->return = $return;
    }

    public function handle(Throwable $e, RequestInterface $request): ResponseInterface
    {
        return call_user_func($this->return, $e, $request);
    }

}