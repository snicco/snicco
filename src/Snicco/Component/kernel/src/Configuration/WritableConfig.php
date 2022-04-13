<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use LogicException;
use Snicco\Component\StrArr\Arr;
use Webmozart\Assert\Assert;

use function array_replace;
use function array_unique;
use function array_unshift;
use function array_values;
use function gettype;
use function pathinfo;
use function sprintf;

use const PATHINFO_FILENAME;

final class WritableConfig extends Config
{
    private array $items;

    /**
     * @param mixed[] $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * Keys that are already present in the current configuration are not
     * replaced.
     *
     * @param array<string, mixed> $defaults can be a multi-dimensional array, but all values must be scalar|null
     */
    public function mergeDefaults(string $key, array $defaults): void
    {
        $current = $this->get($key, []);

        Assert::isArray($current);

        $with_defaults = array_replace($defaults, $current);

        $this->set($key, $with_defaults);
    }

    public function mergeDefaultsFromFile(string $config_file): void
    {
        Assert::readable($config_file);

        /** @psalm-suppress UnresolvableInclude */
        $config = require $config_file;

        Assert::isArray($config);
        Assert::true(Arr::isAssoc($config), sprintf('config in %s must be an associative array.', $config_file));

        $name = pathinfo($config_file, PATHINFO_FILENAME);
        $this->mergeDefaults($name, $config);
    }

    /**
     * @param scalar|scalar[] $value
     *
     * @throws LogicException if key is missing or not a numerical array
     * @throws LogicException if value has a different type than the list values
     */
    public function appendToList(string $key, $value): void
    {
        if (! $this->has($key)) {
            throw new LogicException(sprintf('Cant append to missing config key [%s].', $key));
        }

        $current = $this->get($key);

        Assert::isArray($current);
        Assert::isList($current, sprintf('Cant append to key [%s] because its not a list.', $key));

        $type = isset($current[0]) ? gettype($current[0]) : null;

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [{$type}].\nGot [{$actual}].");
            }

            $current[] = $item;
        }

        $this->set($key, array_unique($current));
    }

    /**
     * @note Assuming you have [4,5,6]. $config->prepend([1,2,3]) will result in [3,2,1,4,5,6].
     *       Arrays are NOT merged. Each value is prepended individually.
     *
     * @param scalar|scalar[] $value
     *
     * @throws LogicException if key is missing or not a numerical array
     * @throws LogicException if value has a different type than the list values
     */
    public function prependToList(string $key, $value): void
    {
        if (! $this->has($key)) {
            throw new LogicException(sprintf('Cant prepend to missing config key [%s].', $key));
        }

        $current = $this->get($key);

        Assert::isArray($current);
        Assert::isList($current, sprintf('Cant prepend to key [%s] because its not a list.', $key));

        $type = isset($current[0]) ? gettype($current[0]) : null;

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [{$type}].\nGot [{$actual}].");
            }

            array_unshift($current, $item);
        }

        $this->set($key, array_values(array_unique($current)));
    }

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->items, $key, $value);
    }

    /**
     * @param mixed $value
     */
    public function setIfMissing(string $key, $value): void
    {
        if (! $this->has($key)) {
            $this->set($key, $value);
        }
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
