<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;

/**
 * @psalm-immutable
 *
 * @template-implements IteratorAggregate<int,Package>
 */
final class PackageCollection implements Countable, JsonSerializable, IteratorAggregate
{
    /**
     * @var array<string,Package>
     */
    private array $packages = [];

    /**
     * @param Package[] $packages
     */
    public function __construct(array $packages)
    {
        foreach ($packages as $package) {
            $id = $package->full_name;
            if (isset($this->packages[$id])) {
                continue;
            }

            $this->packages[$id] = $package;
        }

        ksort($this->packages);
    }

    /**
     * @param callable(Package):bool $filter
     *
     * @psalm-param pure-callable(Package):bool $filter
     */
    public function filter(callable $filter): PackageCollection
    {
        $packages = array_filter($this->packages, $filter);

        return new self($packages);
    }

    /**
     * @return array<array{name: string, vendor_name:string, full_name:string, composer_json_path: string, relative_path: string, absolute_path:string}>
     */
    public function toArray(): array
    {
        return array_values(array_map(fn(Package $package): array => $package->toArray(), $this->packages));
    }

    public function merge(PackageCollection $collection): PackageCollection
    {
        return new self(array_merge($this->packages, $collection->packages));
    }

    /**
     * @param non-empty-string $package_full_name
     */
    public function contains(string $package_full_name): bool
    {
        return isset($this->packages[$package_full_name]);
    }

    public function count(): int
    {
        return count($this->packages);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->packages));
    }

    public function get(string $composer_name): Package
    {
        Assert::true(
            isset($this->packages[$composer_name]),
            sprintf('The package [%s] is not in the collection.', $composer_name)
        );

        return $this->packages[$composer_name];
    }
}
