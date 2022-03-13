<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use InvalidArgumentException;

use function array_intersect;
use function array_values;
use function count;
use function sprintf;

/**
 * @internal
 *
 * @psalm-internal Snicco\Monorepo\Package
 */
final class GetDependentPackages
{
    private array $building = [];

    public function __invoke(PackageCollection $packages, PackageCollection $all_packages): PackageCollection
    {
        return $this->resolveDependents($packages, $all_packages);
    }

    private function resolveDependents(PackageCollection $packages, PackageCollection $all_packages): PackageCollection
    {
        $initial_names = $this->composerJsonNames($packages);

        $intersect = array_intersect($initial_names, $this->building);

        if ([] !== $intersect) {
            $first = array_values($intersect)[0];

            throw new InvalidArgumentException(
                sprintf(
                    "Recursion detected.\nPackage [%s] requires a package that itself requires package [%s] somewhere in the dependency chain.",
                    $first,
                    $first
                )
            );
        }

        $this->building += $initial_names;

        $packages = $all_packages->filter(function (Package $package) use ($initial_names): bool {
            $requires = $package->firstPartyDependencies();

            return [] !== array_intersect($requires, $initial_names);
        });

        if (0 === count($packages)) {
            $this->building = [];

            return $packages;
        }

        return $packages->merge($this->resolveDependents($packages, $all_packages));
    }

    /**
     * @return list<string>
     */
    private function composerJsonNames(PackageCollection $packages): array
    {
        $names = [];

        foreach ($packages as $package) {
            $names[] = $package->full_name;
        }

        return $names;
    }
}
