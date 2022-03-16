<?php

declare(strict_types=1);

namespace Snicco\Monorepo;

use InvalidArgumentException;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

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
        Assert::stringNotEmpty($key);
        Assert::startsWith($key, '--');
        Assert::notEndsWith($key, '=');

        /**
         * @var mixed $input
         */
        foreach ($this->argv as $input) {
            $input = (string) $input;

            if (Str::startsWith($input, $key . '=')) {
                return Str::afterFirst($input, $key . '=');
            }
        }

        throw new InvalidArgumentException(sprintf('Required input [%s] not provided.', $key));
    }

    public function mainArg(): string
    {
        Assert::true(isset($this->argv[1]), 'Main input not provided.');
        $value = $this->argv[1];
        Assert::string($value);

        return $value;
    }
}
