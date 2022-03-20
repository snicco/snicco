<?php

declare(strict_types=1);

namespace Snicco\Monorepo;

use InvalidArgumentException;

use function array_reverse;
use function explode;
use function is_string;
use function ltrim;
use function rtrim;
use function strlen;
use function strncmp;

/**
 * @note This class must not use any composer dependencies.
 */
final class Input
{
    private array $argv;

    /**
     * @param mixed[] $argv
     */
    public function __construct(array $argv)
    {
        $this->argv = $argv;
    }

    public function parse(string $key): string
    {
        $value = $this->parseOptional($key);

        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf('Required input [%s] not provided.', $key));
    }

    public function parseOptional(string $key): ?string
    {
        if ('' === $key) {
            throw new InvalidArgumentException('$key can not be empty string.');
        }

        $key = '--' . ltrim($key, '-');
        $key = rtrim($key, '=') . '=';

        /**
         * @var mixed $input
         */
        foreach ($this->argv as $input) {
            $input = (string) $input;

            if ($this->startsWith($input, $key)) {
                return $this->afterFirst($input, $key);
            }
        }

        return null;
    }

    public function mainArg(): string
    {
        if (! isset($this->argv[1])) {
            throw new InvalidArgumentException('Main input not provided');
        }

        $input = $this->argv[1];
        if (! is_string($input)) {
            throw new InvalidArgumentException('Main input is not a string.');
        }

        return $input;
    }

    private function startsWith(string $subject, string $needle): bool
    {
        if ('' === $needle) {
            return false;
        }

        return 0 === strncmp($subject, $needle, strlen($needle));
    }

    private function afterFirst(string $subject, string $search): string
    {
        return '' === $search ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }
}
