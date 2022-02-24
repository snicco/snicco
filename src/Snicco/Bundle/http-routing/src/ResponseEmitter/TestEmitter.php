<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ResponseEmitter;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

final class TestEmitter implements ResponseEmitter
{
    private EventDispatcher $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function emit(ResponseInterface $response): void
    {
        $this->dispatcher->dispatch(new GenericEvent('test_emitter', [$response]));
    }
}