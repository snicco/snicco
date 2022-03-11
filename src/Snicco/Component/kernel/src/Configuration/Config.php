<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use Webmozart\Assert\Assert;

abstract class Config
{
    /**
     * @param mixed $default
     *
     * @return mixed
     */
    abstract public function get(string $key, $default = null);

    final public function getString(string $key, ?string $default = null): string
    {
        $val = $this->get($key, $default);
        Assert::string($val, "Expected a string for config key [{$key}].\nGot: [%s]");

        return $val;
    }

    final public function getInteger(string $key, ?int $default = null): int
    {
        $val = $this->get($key, $default);
        Assert::integer($val, "Expected an integer for config key [{$key}].\nGot: [%s]");

        return $val;
    }

    final public function getBoolean(string $key, ?bool $default = null): bool
    {
        $val = $this->get($key, $default);
        Assert::boolean($val, "Expected a boolean for config key [{$key}].\nGot: [%s]");

        return $val;
    }

    /**
     * @param list<string> $default
     *
     * @return list<string>
     */
    final public function getListOfStrings(string $key, ?array $default = null): array
    {
        $val = $this->get($key, $default);
        Assert::isList($val, sprintf('Config value for key [%s] is not a list of strings.', $key));
        Assert::allString($val, "Config value for key [{$key}] is not a list of strings.\nGot: [%s].");

        return $val;
    }

    final public function getArray(string $key, ?array $default = null): array
    {
        $val = $this->get($key, $default);
        Assert::isArray($val, "Expected an array for config key [{$key}].\nGot: [%s]");

        return $val;
    }
}
