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
            if (!is_array($items)) {
                throw new InvalidArgumentException("Reading the [$name] config did not return an array.");
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
                && $file_info->getExtension() === 'php'
            ) {
                $files[pathinfo($file_info->getRealPath(), PATHINFO_FILENAME)] = $file_info->getRealPath();
            }
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

}