<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @api
 */
final class MiddlewareBlueprint
{

    /**
     * @var class-string<MiddlewareInterface>
     */
    private string $class;

    private array $arguments;

    /**
     * @param class-string<MiddlewareInterface> $class
     */
    public function __construct(string $class, array $arguments = [])
    {
        if (!Reflection::isInterface($class, MiddlewareInterface::class)) {
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
     */
    public static function create(string $class, array $arguments = []): MiddlewareBlueprint
    {
        return new self($class, $arguments);
    }

    /**
     * @return class-string<MiddlewareInterface>
     */
    public function class(): string
    {
        return $this->class;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

}