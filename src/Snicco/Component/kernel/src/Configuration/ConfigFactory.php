<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use RuntimeException;
use Snicco\Component\Kernel\ValueObject\PHPCacheFile;
use Symfony\Component\Finder\Finder;

use function count;
use function file_put_contents;
use function is_array;
use function var_export;

/**
 * @interal
 * @psalm-internal Snicco\Component\Kernel
 */
final class ConfigFactory
{

    /**
     * @throws RuntimeException If no config files are found or config cache can't be written.
     */
    public function load(string $config_directory, ?PHPCacheFile $cache_file = null): array
    {
        if (!$cache_file) {
            return $this->loadFromFiles($config_directory);
        }

        return $this->loadFromCache($cache_file);
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    private function loadFromFiles(string $config_directory): array
    {
        $config_files = $this->findConfigFiles($config_directory);

        if (!count($config_files)) {
            throw new RuntimeException(
                "No configuration files found in directory [$config_directory]."
            );
        }

        if (!isset($config_files['app'])) {
            throw new RuntimeException(
                "The [app.php] config file was not found in the config dir [$config_directory]."
            );
        }

        $config = [];

        foreach ($config_files as $name => $path) {
            $items = require $path;
            if (!is_array($items)) {
                throw new RuntimeException("Reading the [$name] config did not return an array.");
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

        foreach (Finder::create()->files()->name('*.php')->in($config_dir) as $file) {
            $files[$file->getFilenameWithoutExtension()] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    private function loadFromCache(PHPCacheFile $cached_config): array
    {
        $items = $cached_config->require();

        if (!is_array($items)) {
            throw new RuntimeException(
                "The cached config did not return an array.\nUsed cache file [{$cached_config->realPath()}]."
            );
        }

        if (!isset($items['app'])) {
            throw new RuntimeException(
                "The [app] key is not present in the cached config.\nUsed cache file [{$cached_config->realpath()}]."
            );
        }

        return $items;
    }

    public function writeToCache(string $realpath, array $config): void
    {
        $success = file_put_contents(
            $realpath,
            '<?php return ' . var_export($config, true) . ';'
        );

        if (false === $success) {
            throw new RuntimeException(
                "Could not write configuration to cache file [$realpath]."
            );
        }
    }

}