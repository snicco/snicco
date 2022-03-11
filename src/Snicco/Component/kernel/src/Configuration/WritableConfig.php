<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use LogicException;
use Snicco\Component\StrArr\Arr;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_merge;
use function array_unique;
use function array_unshift;
use function array_values;
use function count;
use function gettype;
use function is_array;
use function range;

final class WritableConfig extends Config
{
    private array $items;

    public function __construct(?array $items = null)
    {
        $this->items = $items ?: [];
    }

    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * Extend the configuration with the given values. Existing values have priority.
     *
     * @note This method does not work for multidimensional arrays. The existing config has to be an array of scalars.
     *
     * @param array<?scalar>|?scalar $extend_with
     */
    public function extend(string $key, $extend_with): void
    {
        $existing_config = $this->get($key);

        if (null === $existing_config) {
            $this->set($key, $extend_with);

            return;
        }

        if (! is_array($existing_config)) {
            return;
        }

        Assert::allScalar($existing_config);

        $extend_with = is_array($extend_with) ? $extend_with : [$extend_with];

        $new_value = $this->mergedArrayConfig($extend_with, $existing_config);

        $this->set($key, $new_value);
    }

    /**
     * @param scalar|scalar[] $value
     *
     * @throws LogicException if key is missing or not a numerical array
     * @throws LogicException if value has a different type than the list values
     */
    public function append(string $key, $value): void
    {
        if (! $this->has($key)) {
            throw new LogicException("Cant append to missing config key [{$key}].");
        }
        $current = $this->get($key);

        Assert::isArray($current);
        Assert::isList($current, "Cant append to key [{$key}] because its not a list.");

        $type = count($current) ? gettype($current[0]) : null;

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [{$type}].\nGot [{$actual}].");
            }
            $current[] = $item;
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
     * @note Assuming you have [4,5,6]. ->prepend([1,2,3]) will result in [3,2,1,4,5,6].
     *       Arrays are NOT merged. Each value is prepended individually.
     *
     * @param scalar|scalar[] $value
     *
     * @throws LogicException if key is missing or not a numerical array
     * @throws LogicException if value has a different type than the list values
     */
    public function prepend(string $key, $value): void
    {
        if (! $this->has($key)) {
            throw new LogicException("Cant prepend to missing config key [{$key}].");
        }
        $current = $this->get($key);

        Assert::isArray($current);
        Assert::isList($current, "Cant prepend to key [{$key}] because its not a list.");

        $type = count($current) ? gettype($current[0]) : null;

        $value = Arr::toArray($value);

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [{$type}].\nGot [{$actual}].");
            }
            array_unshift($current, $item);
        }

        $this->set($key, array_values(array_unique($current)));
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

    /**
     * @param array<?scalar> $extend_with
     * @param array<?scalar> $exiting_config
     */
    private function mergedArrayConfig(array $extend_with, array $exiting_config): array
    {
        if ($this->isList($extend_with) && $this->isList($exiting_config)) {
            return array_values(array_unique(array_merge($exiting_config, $extend_with)));
        }

        $current = $exiting_config;

        foreach ($extend_with as $key => $value) {
            if (! isset($current[$key])) {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    /**
     * @psalm-assert list $array
     */
    private function isList(array $array): bool
    {
        $count = count($array);

        if (0 === $count) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
