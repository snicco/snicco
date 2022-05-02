<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Synopsis;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPCLI\Check;

use function in_array;

final class InputArgument implements InputDefinition
{
    /**
     * @var int
     */
    public const REQUIRED = 1;

    /**
     * @var int
     */
    public const OPTIONAL = 2;

    /**
     * @var int
     */
    public const REPEATING = 4;

    private string $name;

    private string $description;

    private bool   $optional;

    private ?string $default;

    private bool $repeating;

    /**
     * @var non-empty-list<string>|null
     */
    private ?array $allowed_values;

    /**
     * @param non-empty-string            $name
     * @param non-empty-list<string>|null $allowed_values
     */
    public function __construct(
        string $name,
        string $description = '',
        int $flags = self::REQUIRED,
        string $default = null,
        array $allowed_values = null
    ) {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $name) {
            throw new InvalidArgumentException('name can not be empty');
        }

        $this->name = $name;
        $this->description = $description;

        if ($flags > 7 || $flags < 1) {
            throw new InvalidArgumentException(sprintf('Argument flag [%s] is not valid.', $flags));
        }

        $is_required = self::REQUIRED === (self::REQUIRED & $flags);
        $is_optional = self::OPTIONAL === (self::OPTIONAL & $flags);
        $is_repeating = self::REPEATING === (self::REPEATING & $flags);

        if ($is_optional && $is_required) {
            throw new LogicException('Input argument can not be required and optional.');
        }

        if (! $is_optional && ! $is_required) {
            throw new LogicException('Input argument must be either required or optional.');
        }

        $this->optional = $is_optional;
        $this->repeating = $is_repeating;

        if (null !== $allowed_values) {
            if (! Check::isListOfStrings($allowed_values)) {
                throw new InvalidArgumentException('allowed values must be a list of strings.');
            }

            if (Check::isEmpty($allowed_values)) {
                throw new InvalidArgumentException('allowed values must not be empty.');
            }
        }

        $this->allowed_values = $allowed_values;

        if (null === $default) {
            $this->default = $default;

            return;
        }

        if ($is_required) {
            throw new LogicException('A required argument can not have a default value.');
        }

        if (null !== $this->allowed_values && ! in_array($default, $this->allowed_values, true)) {
            throw new InvalidArgumentException(sprintf(
                'Default value [%s] is not in list of allowed values.',
                $default
            ));
        }

        $this->default = $default;
    }

    /**
     * @interal
     *
     * @return array{type: string, name: string, description: string, optional: bool, repeating: bool, default?: string, options?: string[]}
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function toArray(): array
    {
        $args = [
            'type' => 'positional',
            'name' => $this->name,
            'description' => $this->description,
            'optional' => $this->optional,
            'repeating' => $this->repeating,
        ];

        if (null !== $this->default) {
            $args['default'] = $this->default;
        }

        if (null !== $this->allowed_values) {
            $args['options'] = $this->allowed_values;
        }

        return $args;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @interal
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function isRepeating(): bool
    {
        return $this->repeating;
    }

    /**
     * @interal
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
