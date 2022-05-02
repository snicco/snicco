<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Input;

/**
 * @psalm-immutable
 */
interface Input
{
    /**
     * Retrieves an argument by its name or returns the value passed to $default
     * if no argument with the name was passed in the command-line.
     *
     * @psalm-return (
     * $default is null
     * ? string|null
     * : string
     * )
     */
    public function getArgument(string $name, ?string $default = null): ?string;

    /**
     * Retrieves a repeating argument by its name or returns the value passed to
     * $default if no argument with the name was passed in the command-line.
     *
     * @param string[]|null $default
     *
     * @return string[]|null
     *
     * @psalm-return (
     * $default is null
     * ? string[]|null
     * : string[]
     * )
     */
    public function getRepeatingArgument(string $name, ?array $default = null): ?array;

    /**
     * Retrieves an option by its name or returns the value passed to $default
     * if no option with the name was passed in the command-line.
     *
     * @psalm-return (
     * $default is null
     * ? string|null
     * : string
     * )
     */
    public function getOption(string $name, ?string $default = null): ?string;

    /**
     * Retrieves a flag by its name or returns the value passed to $default if
     * no flag with the name was passed in the command-line.
     *
     * @psalm-return (
     * $default is null
     * ? bool|null
     * : bool
     * )
     */
    public function getFlag(string $name, ?bool $default = null): ?bool;

    /**
     * Returns all arguments keyed by the argument name.
     *
     * @return array<string,string>
     */
    public function getArguments(): array;

    /**
     * Returns all repeating arguments keyed by the argument name.
     *
     * @return array<string,string[]>
     */
    public function getRepeatingArguments(): array;

    /**
     * Returns all options keyed by the option name.
     *
     * @return array<string,string>
     */
    public function getOptions(): array;

    /**
     * Returns all flags keyed by the flag name.
     *
     * @return array<string,bool>
     */
    public function getFlags(): array;

    public function isInteractive(): bool;

    /**
     * @internal
     *
     * @return resource
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function getStream();
}
