<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use LogicException;
use Snicco\Component\ParameterBag\ParameterBag;
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

/**
 * @api
 */
final class WritableConfig extends Config
{

    private ParameterBag $repository;

    public function __construct(?ParameterBag $repository = null)
    {
        $this->repository = $repository ?? new ParameterBag();
    }

    public static function fromArray(array $items): self
    {
        return new self(new ParameterBag($items));
    }

    /**
     * Extend the configuration with the given values.
     * Existing values have priority.
     *
     * @param scalar|array<scalar> $extend_with
     */
    public function extend(string $key, $extend_with): void
    {
        $existing_config = $this->repository->get($key);

        if (null === $existing_config) {
            $this->repository->set($key, $extend_with);
            return;
        }

        if (!is_array($existing_config)) {
            return;
        }

        Assert::allScalar($existing_config);

        $extend_with = is_array($extend_with) ? $extend_with : [$extend_with];

        $new_value = $this->mergedArrayConfig($extend_with, $existing_config);

        $this->repository->set($key, $new_value);
    }

    /**
     * @param scalar|scalar[] $value
     *
     * @throws LogicException If key is missing or not a numerical array.
     * @throws LogicException If value has a different type than the list values.
     */
    public function append(string $key, $value): void
    {
        if (!$this->has($key)) {
            throw new LogicException("Cant append to missing config key [$key].");
        }
        $current = $this->repository->get($key);

        Assert::isArray($current);
        Assert::isList($current, "Cant append to key [$key] because its not a list.");

        $type = count($current) ? gettype($current[0]) : null;

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [$type].\nGot [$actual].");
            }
            $current[] = $item;
        }

        $this->set($key, array_values(array_unique($current)));
    }

    public function has(string $key): bool
    {
        return $this->repository->has($key);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->repository->set($key, $value);
    }

    /**
     * @param scalar|scalar[] $value
     *
     * @throws LogicException If key is missing or not a numerical array.
     * @throws LogicException If value has a different type than the list values.
     */
    public function prepend(string $key, $value): void
    {
        if (!$this->has($key)) {
            throw new LogicException("Cant prepend to missing config key [$key].");
        }
        $current = $this->repository->get($key);

        Assert::isArray($current);
        Assert::isList($current, "Cant prepend to key [$key] because its not a list.");

        $type = count($current) ? gettype($current[0]) : null;

        $value = Arr::toArray($value);

        foreach (Arr::toArray($value) as $item) {
            if (($actual = gettype($item)) !== $type && (null !== $type)) {
                throw new LogicException("Expected scalar type [$type].\nGot [$actual].");
            }
            array_unshift($current, $item);
        }

        $this->set($key, array_values(array_unique($current)));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->repository->get($key, $default);
    }

    public function toArray(): array
    {
        return $this->repository->toArray();
    }

    /**
     * @param array<scalar> $extend_with
     * @param array<scalar> $exiting_config
     */
    private function mergedArrayConfig(array $extend_with, array $exiting_config): array
    {
        if ($this->isList($extend_with) && $this->isList($exiting_config)) {
            return array_values(array_unique(array_merge($exiting_config, $extend_with)));
        }

        $current = $exiting_config;

        foreach ($extend_with as $key => $value) {
            if (!isset($current[$key])) {
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