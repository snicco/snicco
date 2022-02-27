<?php

declare(strict_types=1);


namespace Snicco\Bundle\Templating;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerFactory;
use Throwable;

final class PsrViewComposerFactory implements ViewComposerFactory
{

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function create($composer): ViewComposer
    {
        try {
            return $this->container->get($composer);
        } catch (NotFoundExceptionInterface $e) {
            //
        }
        try {
            return (new ReflectionClass($composer))->newInstance();
        } catch (Throwable $e) {
        }

        throw new InvalidArgumentException(
            "Composer [$composer] can't be created with the container and is not a newable class.\n{$e->getMessage()}"
        );
    }
}