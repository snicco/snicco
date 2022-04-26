<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Synopsis;

use InvalidArgumentException;

final class InputFlag implements InputDefinition
{
    private string $name;

    private string $description;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name, string $description = '')
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $name) {
            throw new InvalidArgumentException('name can not be empty.');
        }

        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @interal
     *
     * @return array{type: string, name: string, description: string, optional: true}
     *
     * @psalm-internal Snicco\Component\BetterWPCLI
     */
    public function toArray(): array
    {
        return [
            'type' => 'flag',
            'name' => $this->name,
            'description' => $this->description,
            'optional' => true,
        ];
    }

    public function name(): string
    {
        return $this->name;
    }
}
