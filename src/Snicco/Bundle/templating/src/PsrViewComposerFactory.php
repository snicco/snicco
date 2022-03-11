<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use Snicco\Component\Templating\Exception\BadViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerFactory;
use Throwable;

use function gettype;
use function sprintf;

final class PsrViewComposerFactory implements ViewComposerFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @template T of ViewComposer
     *
     * @param class-string<T> $composer
     *
     * @throws BadViewComposer
     *
     * @return T
     */
    public function create($composer): ViewComposer
    {
        try {
            $instance = $this->container->get($composer);
            if (! $instance instanceof $composer) {
                throw new BadViewComposer(sprintf('PSR container did not return instance [%s] but [%s]', ViewComposer::class, gettype($instance)));
            }
            /**
             * @psalm-var  T $instance
             */
            return $instance;
        } catch (NotFoundExceptionInterface $e) {
        }

        try {
            return (new ReflectionClass($composer))->newInstance();
        } catch (Throwable $e) {
        }

        throw new BadViewComposer(
            "Composer [{$composer}] can't be created with the container and is not a newable class.\n{$e->getMessage()}"
        );
    }
}
