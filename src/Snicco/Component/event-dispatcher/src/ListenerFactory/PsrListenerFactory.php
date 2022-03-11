<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;

use function gettype;
use function is_object;
use function sprintf;

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
            $obj = $this->psr_container->get($listener_class);
            if (! is_object($obj)) {
                throw new InvalidArgumentException(sprintf(
                    '$this->psr_container->get($listener_class) should return an object. Got [%s]',
                    gettype($obj)
                ));
            }

            return $obj;
        } catch (ContainerExceptionInterface $e) {
            throw CantCreateListener::fromPrevious($listener_class, $event_name, $e);
        }
    }
}
