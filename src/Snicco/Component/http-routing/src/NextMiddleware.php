<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @api
 */
final class NextMiddleware implements RequestHandlerInterface, MiddlewareInterface
{
    
    /**
     * @var callable
     */
    private $callback;
    
    /**
     * @param  callable  $callback  function (RequestInterface $request) : ResponseInterface
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    
    /**
     * Dispatch the next available middleware and return the response.
     * This method duplicates `handle()` to provide support for `callable` middleware.
     */
    public function __invoke(Request $request) :Response
    {
        $psr_response = $this->handle($request);
        if ( ! $psr_response instanceof Response) {
            $psr_response = new Response($psr_response);
        }
        return $psr_response;
    }
    
    public function handle(ServerRequestInterface $request) :ResponseInterface
    {
        return ($this->callback)($request);
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        return $this->handle($request);
    }
    
}