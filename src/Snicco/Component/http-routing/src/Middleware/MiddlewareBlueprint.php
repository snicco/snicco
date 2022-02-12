<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Server\MiddlewareInterface;

/**
 * @interal
 *
 * @psalm-immutable
 */
final class MiddlewareBlueprint
{

    /**
     * @var class-string<MiddlewareInterface>
     */
    public string $class;

    /**
     * @var array<scalar>
     */
    public array $arguments;

    /**
     * @param class-string<MiddlewareInterface> $class
     * @param array<scalar> $arguments
     */
    public function __construct(string $class, array $arguments = [])
    {
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

}