<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Synopsis;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPCLI\Check;

use function in_array;
use function sprintf;

final class InputOption implements InputDefinition
{
    /**
     * @var int
     */
    public const OPTIONAL = 1;

    /**
     * @var int
     */
    public const REQUIRED = 2;

    /**
     * @var int
     */
    public const COMMA_SEPARATED = 4;

    private string $name;

    private string $description;

    private bool $optional;

    private bool $comma_separated;

    private ?string $default;

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
        int $flags = self::OPTIONAL,
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
            throw new InvalidArgumentException(sprintf('Flag [%s] is not valid.', $flags));
        }

        $optional = self::OPTIONAL === (self::OPTIONAL & $flags);
        $required = self::REQUIRED === (self::REQUIRED & $flags);

        if ($optional && $required) {
            throw new LogicException('Input option can not be required and optional.');
        }

        if (! $optional && ! $required) {
            throw new LogicException('Input option must be either required or optional.');
        }

        $this->comma_separated = self::COMMA_SEPARATED === (self::COMMA_SEPARATED & $flags);
        $this->optional = $optional;

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

        if ($required) {
            throw new LogicException('A required argument can not have a default value.');
        }

        if (null !== $this->allowed_values && ! in_array($default, $this->allowed_values, true)) {
            throw new InvalidArgumentException(
                sprintf('Default value [%s] is not in list of allowed values.', $default)
            );
        }

        $this->default = $default;
    }

    /**
     * @internal
     *
     * @return array{type: string, name: string, description: string, optional: bool, repeating?: true, default?:string, options?: string[]}
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function toArray(): array
    {
        $data = [
            'type' => 'assoc',
            'name' => $this->name,
            'description' => $this->description,
            'optional' => $this->optional,
        ];

        if ($this->comma_separated) {
            $data['description'] .= ' (supports multiple comma-separated values)';
            $data['repeating'] = true;
        }

        if (null !== $this->default) {
            $data['default'] = $this->default;
        }

        if (null !== $this->allowed_values) {
            $data['options'] = $this->allowed_values;
        }

        return $data;
    }

    public function name(): string
    {
        return $this->name;
    }
}
