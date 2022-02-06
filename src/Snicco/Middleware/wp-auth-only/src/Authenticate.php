<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPAuth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\ScopableWP\ScopableWP;

use function sprintf;

final class Authenticate implements MiddlewareInterface
{

    const KEY = '_user_id';
    private ScopableWP $wp;

    public function __construct(ScopableWP $wp = null)
    {
        $this->wp = $wp ?: new ScopableWP();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->wp->isUserLoggedIn()) {
            return $handler->handle(
                $request->withAttribute(
                    self::KEY,
                    $this->wp->getCurrentUserId()
                )
            );
        }

        throw new HttpException(
            401,
            sprintf(
                'Missing authentication for request path [%s].',
                $request->getUri()->getPath()
            )
        );
    }
}