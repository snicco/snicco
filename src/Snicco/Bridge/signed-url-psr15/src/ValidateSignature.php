<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlPsr15;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\SignedUrl\SignedUrlValidator;

use function call_user_func;
use function in_array;
use function is_callable;

final class ValidateSignature implements MiddlewareInterface
{
    private SignedUrlValidator $validator;

    /**
     * @var Closure(ServerRequestInterface):string|null
     */
    private ?Closure $request_context;

    private bool $check_only_unsafe_methods;

    /**
     * @param Closure(ServerRequestInterface):string|null $request_context
     */
    public function __construct(
        SignedUrlValidator $validator,
        ?Closure $request_context = null,
        bool $check_only_for_unsafe_http_methods = false
    ) {
        $this->validator = $validator;
        $this->request_context = $request_context;
        $this->check_only_unsafe_methods = $check_only_for_unsafe_http_methods;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->check_only_unsafe_methods && ! in_array(
            $request->getMethod(),
            ['POST', 'PUT', 'DELETE', 'PATCH'],
            true
        )) {
            return $handler->handle($request);
        }

        $context = is_callable($this->request_context)
            ? call_user_func($this->request_context, $request)
            : '';

        $this->validator->validate($request->getRequestTarget(), $context);

        return $handler->handle($request);
    }
}
