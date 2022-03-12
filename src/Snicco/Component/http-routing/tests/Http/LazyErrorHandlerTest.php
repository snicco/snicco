<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use Closure;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\HttpRouting\LazyHttpErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Throwable;

use function call_user_func;

/**
 * @internal
 */
final class LazyErrorHandlerTest extends TestCase
{
    use CreateTestPsr17Factories;

    private Container $pimple;

    private \Pimple\Psr11\Container $psr_container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pimple = new Container();
        $this->psr_container = new \Pimple\Psr11\Container($this->pimple);
    }

    /**
     * @test
     */
    public function the_lazy_error_handler_behaves_the_same_as_the_real_error_handler_it_proxies_to(): void
    {
        $this->pimple[HttpErrorHandler::class] = fn (): TestableErrorHandler => new TestableErrorHandler(
            function (): void {
                throw new Exception('Should never be called');
            }
        );
        $lazy_handler = new LazyHttpErrorHandler($this->psr_container);

        $this->assertInstanceOf(HttpErrorHandler::class, $lazy_handler);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_lazy_error_handler_doesnt_have_the_http_error_handler_interface(
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The psr container needs a service for id [' . HttpErrorHandler::class . '].'
        );

        new LazyHttpErrorHandler($this->psr_container);
    }

    /**
     * @test
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function calls_are_proxies_to_the_real_handler(): void
    {
        $count = 0;

        $real_handler = new TestableErrorHandler(function (): ResponseInterface {
            $response = $this->psrResponseFactory()
                ->createResponse(500);
            $response->getBody()
                ->write('foo error');

            return $response;
        });

        $this->pimple[HttpErrorHandler::class] = function () use (&$count, $real_handler): TestableErrorHandler {
            ++$count;

            return $real_handler;
        };

        $lazy_handler = new LazyHttpErrorHandler($this->psr_container);

        $response = $lazy_handler->handle(
            new Exception('secret stuff'),
            $this->psrServerRequestFactory()
                ->createServerRequest('GET', '/foo')
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('foo error', (string) $response->getBody());
    }
}

final class TestableErrorHandler implements HttpErrorHandler
{
    /**
     * @var Closure(Throwable, ServerRequestInterface) :ResponseInterface
     */
    private Closure $return;

    /**
     * @param Closure(Throwable, ServerRequestInterface) :ResponseInterface $return
     */
    public function __construct(Closure $return)
    {
        $this->return = $return;
    }

    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        return call_user_func($this->return, $e, $request);
    }
}
