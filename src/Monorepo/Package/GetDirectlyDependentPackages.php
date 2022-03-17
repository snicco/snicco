<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use function in_array;

/**
 * @internal
 *
 * @psalm-internal Snicco\Monorepo\Package
 */
final class GetDirectlyDependentPackages
{

    public function __invoke(PackageCollection $packages, PackageCollection $all_packages): PackageCollection
    {
        return $this->resolveDependents($packages, $all_packages);
    }

    private function resolveDependents(PackageCollection $packages, PackageCollection $all_packages): PackageCollection
    {
        $direct_dependents = new PackageCollection([]);

        foreach ($packages as $package) {
            $direct_dependents = $direct_dependents->merge(
                $this->resolveDependentsForPackage($package->full_name, $all_packages)
            );
        }

        return $direct_dependents;
    }

    private function resolveDependentsForPackage(string $name, PackageCollection $all_packages): PackageCollection
    {
        return $all_packages->filter(
            fn(Package $package): bool => in_array(
                $name,
                $package->firstPartyDependencyNames(),
                true
            )
        );
    }

}
