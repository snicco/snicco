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
     * @psalm-return ($default is string ? string : string|null)
     */
    public function getArgument(string $name, string $default = null): ?string;

    /**
     * Retrieves a repeating argument by its name or returns the value passed to
     * $default if no argument with the name was passed in the command-line.
     *
     * @param string[]|null $default
     *
     * @return string[]|null
     *
     * @psalm-return ($default is array ? string[] : string[]|null)
     */
    public function getRepeatingArgument(string $name, array $default = null): ?array;

    /**
     * Retrieves an option by its name or returns the value passed to $default
     * if no option with the name was passed in the command-line.
     *
     * @psalm-return ($default is string ? string : string|null)
     */
    public function getOption(string $name, string $default = null): ?string;

    /**
     * Retrieves a flag by its name or returns the value passed to $default if
     * no flag with the name was passed in the command-line.
     *
     * @psalm-return ($default is bool ? bool : bool|null)
     */
    public function getFlag(string $name, bool $default = null): ?bool;

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
