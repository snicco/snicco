<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Synopsis;

use InvalidArgumentException;

use function array_map;
use function array_values;
use function get_class;
use function gettype;
use function is_iterable;
use function is_object;
use function sprintf;

final class Synopsis
{
    /**
     * @var array<string,InputDefinition>
     */
    private array $definitions = [];

    private ?InputArgument $repeating_and_positional = null;

    private ?InputArgument $optional_and_positional = null;

    public function __construct(InputDefinition ...$definitions)
    {
        $this->add($definitions);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_values(
            array_map(fn (InputDefinition $definition): array => $definition->toArray(), $this->definitions)
        );
    }

    /**
     * @param InputDefinition|iterable<InputDefinition> $definitions
     */
    public function with($definitions): self
    {
        $new = clone $this;
        $new->add($definitions);

        return $new;
    }

    /**
     * @interal
     *
     * @return array<string,InputArgument>
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     * @psalm-mutation-free
     */
    public function positionalArguments(): array
    {
        $positional = [];
        foreach ($this->definitions as $name => $definition) {
            if (! $definition instanceof InputArgument) {
                continue;
            }

            $positional[$name] = $definition;
        }

        return $positional;
    }

    /**
     * @interal
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     * @psalm-mutation-free
     */
    public function hasRepeatingPositionalArgument(): bool
    {
        return isset($this->repeating_and_positional);
    }

    /**
     * @param InputDefinition|iterable<InputDefinition> $definitions
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    private function add($definitions): void
    {
        $definitions = is_iterable($definitions) ? $definitions : [$definitions];

        foreach ($definitions as $definition) {
            if (! $definition instanceof InputDefinition) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s is not an instance of %s',
                        is_object($definition) ? get_class($definition) : gettype($definition),
                        InputDefinition::class
                    )
                );
            }

            $name = $definition->name();

            if (isset($this->definitions[$name])) {
                throw new InvalidArgumentException(sprintf(
                    'Duplicate input name [%s] is not allowed in synopsis.',
                    $name
                ));
            }

            if ($definition instanceof InputArgument) {
                if (null !== $this->repeating_and_positional) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Positional argument [%s] can not be added after repeating positional argument [%s].',
                            $name,
                            $this->repeating_and_positional->name()
                        )
                    );
                }

                if ($this->optional_and_positional && ! $definition->isOptional()) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Required argument [%s] can not be added after optional argument [%s].',
                            $name,
                            $this->optional_and_positional->name()
                        )
                    );
                }

                if ($definition->isRepeating()) {
                    $this->repeating_and_positional = $definition;
                }

                if ($definition->isOptional()) {
                    $this->optional_and_positional = $definition;
                }
            }

            $this->definitions[$name] = $definition;
        }
    }
}
