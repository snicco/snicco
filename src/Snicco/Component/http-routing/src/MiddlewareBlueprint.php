<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareBlueprint
{

    /**
     * @var class-string<MiddlewareInterface>
     */
    private string $class;

    /**
     * @var array<scalar>
     */
    private array $arguments;

    /**
     * @param class-string<MiddlewareInterface> $class
     * @param array<scalar> $arguments
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public function __construct(string $class, array $arguments = [])
    {
        if (!Reflection::isInterfaceString($class, MiddlewareInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected $class to implement interface [%s]. Got: [%s]',
                    MiddlewareInterface::class,
                    $class
                )
            );
        }
        $this->class = $class;
        $this->arguments = $arguments;
    }

    /**
     * @param class-string<MiddlewareInterface> $class
     * @param array<scalar> $arguments
     */
    public static function from(string $class, array $arguments = []): MiddlewareBlueprint
    {
        return new self($class, $arguments);
    }

    /**
     * @return array{class: class-string<MiddlewareInterface>, args: array<scalar>}
     */
    public function asArray(): array
    {
        return [
            'class' => $this->class,
            'args' => $this->arguments
        ];
    }

    /**
     * @return class-string<MiddlewareInterface>
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return array<scalar>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

}