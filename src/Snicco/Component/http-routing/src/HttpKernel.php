<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Closure;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Component\HttpRouting\Exception\RequestHasNoType;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

final class HttpKernel
{

    private MiddlewarePipeline $pipeline;
    private KernelMiddleware $kernel_middleware;
    private EventDispatcherInterface $event_dispatcher;

    public function __construct(
        KernelMiddleware $kernel_middleware,
        MiddlewarePipeline $pipeline,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->kernel_middleware = $kernel_middleware;
        $this->pipeline = $pipeline;
        $this->event_dispatcher = $event_dispatcher;
    }

    public function handle(Request $request): Response
    {
        $this->validateRequest($request);

        return $this->pipeline->send($request)
            ->through($this->kernel_middleware->asArray())
            ->then($this->handleExhaustedPipeline());
    }

    private function validateRequest(Request $request): void
    {
        try {
            // This will throw an exception if no type is set.
            $request->isToFrontend();
        } catch (RequestHasNoType $e) {
            throw new LogicException(
                'The HttpKernel tried to handle a request without a declared type. This is not allowed.'
            );
        }
    }

    /**
     * @return Closure(Request):never
     */
    private function handleExhaustedPipeline(): Closure
    {
        // This should never happen.
        return function (Request $request) {
            throw new LogicException(
                sprintf(
                    'Middleware stack returned no response for request [%s].',
                    (string)$request->getUri()
                )
            );
        };
    }

}

