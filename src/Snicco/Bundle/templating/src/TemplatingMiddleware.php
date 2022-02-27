<?php

declare(strict_types=1);


namespace Snicco\Bundle\Templating;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Templating\ViewEngine;

final class TemplatingMiddleware extends Middleware
{
    /**
     * @var callable():ViewEngine
     */
    private $view_engine;

    /**
     * @param callable():ViewEngine $view_engine
     */
    public function __construct(callable $view_engine)
    {
        // We make this a callable because the process of resolving the ViewEngine involves invoking several classes and
        // we might only ever need this for some requests.
        $this->view_engine = $view_engine;
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if (!$response instanceof ViewResponse) {
            return $response;
        }

        $body = $this->getViewEngine()->render($response->view(), $response->viewData());

        return (new Response($response))->withBody(
            $this->responseFactory()->createStream($body)
        );
    }

    private function getViewEngine(): ViewEngine
    {
        return ($this->view_engine)();
    }
}