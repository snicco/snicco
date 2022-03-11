<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class MiddlewareBlueprint
{
    /**
     * @var class-string<MiddlewareInterface>
     */
    public string $class;

    /**
     * @var array<string>
     */
    public array $arguments = [];

    /**
     * @param class-string<MiddlewareInterface> $class
     * @param array<string>                     $arguments
     */
    public function __construct(string $class, array $arguments = [])
    {
        $this->class = $class;
        Assert::allString($arguments);
        $this->arguments = $arguments;
    }

    /**
     * @param class-string<MiddlewareInterface> $class
     * @param array<string>                     $arguments
     */
    public static function from(string $class, array $arguments = []): MiddlewareBlueprint
    {
        return new self($class, $arguments);
    }

    /**
     * @return array{class: class-string<MiddlewareInterface>, args: array<string>}
     */
    public function asArray(): array
    {
        return [
            'class' => $this->class,
            'args' => $this->arguments,
        ];
    }
}
