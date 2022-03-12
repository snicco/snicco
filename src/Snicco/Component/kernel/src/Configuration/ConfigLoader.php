<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use FilesystemIterator;
use InvalidArgumentException;
use SplFileInfo;

use function is_array;
use function pathinfo;

use const PATHINFO_FILENAME;

/**
 * @interal
 * @psalm-internal Snicco\Component\Kernel
 */
final class ConfigLoader
{
    /**
     * @return array<string,array>
     */
    public function __invoke(string $config_directory): array
    {
        $config_files = $this->findConfigFiles($config_directory);

        $config = [];

        foreach ($config_files as $name => $path) {
            /** @psalm-suppress UnresolvableInclude */
            $items = require $path;
            if (! is_array($items)) {
                throw new InvalidArgumentException(sprintf('Reading the [%s] config did not return an array.', $name));
            }

            $config[$name] = $items;
        }

        return $config;
    }

    /**
     * @return array<string,string>
     */
    private function findConfigFiles(string $config_dir): array
    {
        $files = [];

        $file_infos = new FilesystemIterator($config_dir);

        /** @var SplFileInfo $file_info */
        foreach ($file_infos as $file_info) {
            if (
                $file_info->isFile()
                && $file_info->isReadable()
                && 'php' === $file_info->getExtension()
            ) {
                $files[pathinfo($file_info->getRealPath(), PATHINFO_FILENAME)] = $file_info->getRealPath();
            }
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }
}
