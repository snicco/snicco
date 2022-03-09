<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;

final class PsrListenerFactory implements ListenerFactory
{
    private ContainerInterface $psr_container;

    public function __construct(ContainerInterface $psr_container)
    {
        $this->psr_container = $psr_container;
    }

    public function create(string $listener_class, string $event_name): object
    {
        try {
            /** @var object $class */
            return $this->psr_container->get($listener_class);
        } catch (ContainerExceptionInterface $e) {
            throw CantCreateListener::fromPrevious($listener_class, $event_name, $e);
        }
    }
}
