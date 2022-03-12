<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use InvalidArgumentException;
use JsonException;
use Snicco\Component\StrArr\Str;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

use function array_map;
use function file_exists;
use function ltrim;

use const DIRECTORY_SEPARATOR;

final class PackageProvider
{
    /**
     * @var non-empty-string
     */
    private string $repository_root_dir;

    /**
     * @var non-empty-array<string>
     */
    private array $package_directories;

    /**
     * @param non-empty-string $repository_root_dir
     * @param string[]         $package_directories
     */
    public function __construct(string $repository_root_dir, array $package_directories)
    {
        $this->repository_root_dir = $repository_root_dir;
        Assert::notEmpty($package_directories);
        Assert::allReadable($package_directories);
        $this->package_directories = $package_directories;
    }

    /**
     * @throws JsonException
     */
    public function getAll(): PackageCollection
    {
        $info = [];

        foreach ($this->packagePaths($this->package_directories) as $dir) {
            $dir_path_absolute = $dir->getRealPath();

            $composer_json = ComposerJson::for($dir_path_absolute . DIRECTORY_SEPARATOR . 'composer.json');

            $package = new Package(
                Str::afterFirst($dir_path_absolute, $this->repository_root_dir),
                $dir_path_absolute,
                $composer_json
            );

            $info[] = $package;
        }

        return new PackageCollection($info);
    }

    /**
     * @throws JsonException
     */
    public function getAffected(array $changed_files): PackageCollection
    {
        $modified_files_abs_path = array_map(
            fn (string $file): string => $this->makeAbsolute($file),
            $changed_files
        );

        $all_packages = $this->getAll();

        $directly_affected_packages = $all_packages->filter(function (Package $package) use (
            $modified_files_abs_path
        ): bool {
            foreach ($modified_files_abs_path as $file) {
                if (Str::startsWith($file, $package->package_dir_abs)) {
                    return true;
                }
            }

            return false;
        });

        $indirectly_affected_packages = (new GetDependentPackages())(
            $directly_affected_packages,
            $all_packages
        );

        return $directly_affected_packages->merge($indirectly_affected_packages);
    }

    /**
     * @param string[] $directories
     */
    private function packagePaths(array $directories): Finder
    {
        Assert::allReadable($directories);

        return Finder::create()
            ->in($directories)
            ->directories()
            ->depth('== 0');
    }

    private function makeAbsolute(string $file): string
    {
        if (file_exists($file)) {
            return $file;
        }

        $file = $this->repository_root_dir . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);

        if (! file_exists($file)) {
            throw new InvalidArgumentException(sprintf('Invalid [%s] provided.', $file));
        }

        return $file;
    }
}
