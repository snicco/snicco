<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Throwable;

use function sprintf;

final class LazyHttpErrorHandler implements HttpErrorHandlerInterface
{

    private ContainerInterface $psr_container;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private HttpErrorHandlerInterface $error_handler;

    public function __construct(ContainerInterface $c)
    {
        if (!$c->has(HttpErrorHandlerInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The psr container needs a service for id [%s].',
                    HttpErrorHandlerInterface::class
                )
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
        if (!isset($this->error_handler)) {
            /** @var HttpErrorHandlerInterface error_handler */
            $this->error_handler = $this->psr_container->get(HttpErrorHandlerInterface::class);
        }
        return $this->error_handler->handle($e, $request);
    }

}