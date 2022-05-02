<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Input;

/**
 * @psalm-immutable
 */
final class ArrayInput implements Input
{
    /**
     * @var resource
     */
    private $stream;

    private bool $interactive;

    /**
     * @var array<string,string>
     */
    private array $arguments;

    /**
     * @var array<string,string[]>
     */
    private array $repeating_arguments;

    /**
     * @var array<string,string>
     */
    private array $options;

    /**
     * @var array<string,bool>
     */
    private array $flags;

    /**
     * @param resource               $stream
     * @param array<string,string>   $arguments
     * @param array<string,string[]> $repeating_arguments
     * @param array<string,string>   $options
     * @param array<string,bool>     $flags
     */
    public function __construct(
        $stream,
        bool $interactive = true,
        array $arguments = [],
        array $repeating_arguments = [],
        array $options = [],
        array $flags = []
    ) {
        $this->stream = $stream;
        $this->interactive = $interactive;
        $this->arguments = $arguments;
        $this->repeating_arguments = $repeating_arguments;
        $this->options = $options;
        $this->flags = $flags;
    }

    public function getArgument(string $name, ?string $default = null): ?string
    {
        return $this->arguments[$name] ?? $default;
    }

    public function getRepeatingArgument(string $name, ?array $default = null): ?array
    {
        return $this->repeating_arguments[$name] ?? $default;
    }

    public function getOption(string $name, ?string $default = null): ?string
    {
        return $this->options[$name] ?? $default;
    }

    public function getFlag(string $name, ?bool $default = null): ?bool
    {
        return $this->flags[$name] ?? $default;
    }

    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getRepeatingArguments(): array
    {
        return $this->repeating_arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }
}
