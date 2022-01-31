<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Throwable;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;

use function sprintf;

final class LazyHttpErrorHandler implements HttpErrorHandlerInterface
{
    
    private ContainerInterface        $psr_container;
    private HttpErrorHandlerInterface $error_handler;
    
    public function __construct(ContainerInterface $c)
    {
        if ( ! isset($c[HttpErrorHandlerInterface::class])) {
            throw new InvalidArgumentException(
                sprintf(
                    "The psr container needs a service for id [%s].",
                    HttpErrorHandlerInterface::class
                )
            );
        }
        $this->psr_container = $c;
    }
    
    public function handle(Throwable $e, RequestInterface $request) :ResponseInterface
    {
        if ( ! isset($this->error_handler)) {
            $this->error_handler = $this->psr_container[HttpErrorHandlerInterface::class];
        }
        return $this->error_handler->handle($e, $request);
    }
    
}