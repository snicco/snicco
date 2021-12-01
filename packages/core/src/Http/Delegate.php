<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delegate implements RequestHandlerInterface, MiddlewareInterface
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
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function __invoke(Request $request) :Response
    {
        return $this->handle($request);
    }
    
    /**
     * Dispatch the next available middleware and return the response.
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function handle(ServerRequestInterface $request) :Response
    {
        return ($this->callback)($request);
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :Response
    {
        return $this->handle($request);
    }
    
}