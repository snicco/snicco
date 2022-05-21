<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Input;

use BadMethodCallException;
use InvalidArgumentException;
use Snicco\Component\BetterWPCLI\Check;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;

use function array_filter;
use function array_shift;
use function count;
use function is_bool;
use function is_numeric;
use function is_string;

use function sprintf;

use const STDIN;

/**
 * @psalm-immutable
 */
final class WPCLIInput implements Input
{
    /**
     * @var array<string,string|null>
     */
    private array $named_args = [];

    /**
     * @var array<string,string[]>
     */
    private array $named_args_repeating = [];

    /**
     * @var array<string,string>
     */
    private array $wp_cli_options = [];

    /**
     * @var array<string,bool>
     */
    private array $wp_cli_flags = [];

    /**
     * @var resource
     */
    private $stream;

    private bool $interactive;

    /**
     * @param resource $stream
     */
    public function __construct(
        Synopsis $synopsis,
        array $wp_cli_positional_args = [],
        array $wp_cli_assoc_args = [],
        $stream = null,
        bool $interactive = true
    ) {
        if (! Check::isListOfStrings($wp_cli_positional_args)) {
            throw new InvalidArgumentException(
                'Received invalid arguments from wp-cli. Positional arguments should be a list of strings.'
            );
        }

        /**
         * @var mixed $value
         */
        foreach ($wp_cli_assoc_args as $name => $value) {
            if (is_numeric($name)) {
                throw new InvalidArgumentException(
                    'Received invalid arguments from wp-cli. Assoc argument keys should not be numerical.'
                );
            }

            if (is_bool($value)) {
                $this->wp_cli_flags[$name] = $value;
            } elseif (is_string($value)) {
                $this->wp_cli_options[$name] = $value;
            } else {
                // Assoc args in wp-cli are always strings. Even "falsy" values are passed as strings.
                // https://github.com/wp-cli/wp-cli/issues/4561#issuecomment-350735161
                throw new InvalidArgumentException(
                    'Received invalid arguments from wp-cli. Assoc arguments should be all be of type array<string,string|bool>'
                );
            }
        }

        $positional = $synopsis->positionalArguments();

        if (count($wp_cli_positional_args) > count($positional) && ! $synopsis->hasRepeatingPositionalArgument()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Received [%s] positional arguments from wp-cli but synopsis only has [%s] positional arguments.',
                    count($wp_cli_positional_args),
                    count($positional)
                )
            );
        }

        foreach ($positional as $argument) {
            if ($argument->isRepeating()) {
                $this->named_args_repeating[$argument->name()] = $wp_cli_positional_args;
            } else {
                $this->named_args[$argument->name()] = array_shift($wp_cli_positional_args);
            }
        }

        $stream = $stream ?: STDIN;

        /** @psalm-suppress DocblockTypeContradiction */
        if (! Check::isStream($stream)) {
            throw new InvalidArgumentException(sprintf('%s needs a stream as its first argument.', self::class, ));
        }

        $this->stream = $stream;
        $this->interactive = $interactive;
    }

    public function getArgument(string $name, ?string $default = null): ?string
    {
        if (isset($this->named_args_repeating[$name])) {
            throw new BadMethodCallException(
                sprintf(
                    'Positional argument [%s] is repeating. Use %s instead.',
                    $name,
                    self::class . '::getRepeatingArgument()'
                )
            );
        }

        return $this->named_args[$name] ?? $default;
    }

    public function getRepeatingArgument(string $name, ?array $default = null): ?array
    {
        if (isset($this->named_args[$name])) {
            throw new BadMethodCallException(
                sprintf(
                    'Positional argument [%s] is not repeating. Use %s instead.',
                    $name,
                    self::class . '::getArgument()'
                )
            );
        }

        return $this->named_args_repeating[$name] ?? $default;
    }

    public function getOption(string $name, ?string $default = null): ?string
    {
        return $this->wp_cli_options[$name] ?? $default;
    }

    public function getFlag(string $name, ?bool $default = null): ?bool
    {
        return $this->wp_cli_flags[$name] ?? $default;
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
        return array_filter($this->named_args, fn ($value) => null !== $value);
    }

    public function getRepeatingArguments(): array
    {
        return $this->named_args_repeating;
    }

    public function getOptions(): array
    {
        return $this->wp_cli_options;
    }

    public function getFlags(): array
    {
        return $this->wp_cli_flags;
    }
}
