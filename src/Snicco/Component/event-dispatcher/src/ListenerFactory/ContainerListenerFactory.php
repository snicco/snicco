<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;

use function sprintf;

/**
 * @api
 */
final class ContainerListenerFactory implements ListenerFactory
{

    private ContainerInterface $psr_container;

    public function __construct(ContainerInterface $psr_container)
    {
        $this->psr_container = $psr_container;
    }


    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function create(string $listener_class, string $event_name): object
    {
        try {
            $class = $this->psr_container->get($listener_class);
            if (!$class instanceof $listener_class) {
                throw new RuntimeException(
                    sprintf('psr container did not return an instance of [%s].', $listener_class)
                );
            }
            return $class;
        } catch (ContainerExceptionInterface $e) {
            throw CantCreateListener::fromPrevious($listener_class, $event_name, $e);
        }
    }

}