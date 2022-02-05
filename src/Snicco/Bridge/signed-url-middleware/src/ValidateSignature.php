<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlMiddleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\SignedUrl\SignedUrlValidator;

final class ValidateSignature implements MiddlewareInterface
{

    private SignedUrlValidator $validator;

    /**
     * @var Closure(ServerRequestInterface):string | null $request_context
     */
    private ?Closure $request_context;

    private bool $check_post_request;

    /**
     * @param Closure(ServerRequestInterface):string | null $request_context
     */
    public function __construct(
        SignedUrlValidator $validator,
        ?Closure $request_context = null,
        bool $check_post_request = false
    ) {
        $this->validator = $validator;
        $this->request_context = $request_context;
        $this->check_post_request = $check_post_request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = is_callable($this->request_context)
            ? call_user_func($this->request_context, $request)
            : '';

        $this->validator->validate($request->getRequestTarget(), $context);

        return $handler->handle($request);
    }

}