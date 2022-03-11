<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Throwable;

use Webmozart\Assert\Assert;

use function sprintf;

final class LazyHttpErrorHandler implements HttpErrorHandler
{
    private ContainerInterface $psr_container;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private HttpErrorHandler $error_handler;

    public function __construct(ContainerInterface $c)
    {
        if (! $c->has(HttpErrorHandler::class)) {
            throw new InvalidArgumentException(
                sprintf('The psr container needs a service for id [%s].', HttpErrorHandler::class)
            );
        }

        $this->psr_container = $c;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        if (! isset($this->error_handler)) {
            $handler = $this->psr_container->get(HttpErrorHandler::class);
            Assert::isInstanceOf($handler, HttpErrorHandler::class);
            $this->error_handler = $handler;
        }

        return $this->error_handler->handle($e, $request);
    }
}
