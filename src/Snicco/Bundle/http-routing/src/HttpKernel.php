<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Bundle\HttpRouting\Event\HandledRequest;
use Snicco\Bundle\HttpRouting\Event\HandlingRequest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;

use function headers_list;
use function microtime;

final class HttpKernel
{
    private MiddlewarePipeline $pipeline;

    private ResponsePreparation $preparation;

    private EventDispatcherInterface $dispatcher;

    /**
     * @var class-string<MiddlewareInterface>[]
     */
    private array $kernel_middleware = [];

    /**
     * @param class-string<MiddlewareInterface>[] $kernel_middleware
     */
    public function __construct(
        MiddlewarePipeline $pipeline,
        ResponsePreparation $preparation,
        EventDispatcherInterface $dispatcher,
        array $kernel_middleware
    ) {
        $this->pipeline = $pipeline;
        $this->preparation = $preparation;
        $this->dispatcher = $dispatcher;
        $this->kernel_middleware = $kernel_middleware;
    }

    public function handle(Request $request): Response
    {
        $this->dispatcher->dispatch(new HandlingRequest($request, microtime(true)));

        $response = $this->pipeline
            ->send($request)
            ->through($this->kernel_middleware)
            ->then(function (): void {
                throw new LogicException(
                    'Kernel middleware pipeline is exhausted without returning a response. This should never happen.'
                );
            });

        $response = $this->preparation->prepare($response, $request, headers_list());

        $this->dispatcher->dispatch(new HandledRequest($request, $response, microtime(true)));

        return $response;
    }
}
