<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Configuration;

use JsonException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Snicco\Component\Core\Utils\CacheFile;

use function json_encode;
use function file_put_contents;

/**
 * @interal
 */
final class ConfigFactory
{
    
    /**
     * @throws JsonException If config can't be decoded from cache.
     * @throws RuntimeException If no config files are found or config cache can't be written.
     */
    public function create(string $config_directory, ?CacheFile $cache_file = null) :WritableConfig
    {
        if ($cache_file && $cache_file->isCreated()) {
            return $this->createFromCache($cache_file);
        }
        
        $config = new WritableConfig();
        
        $config_files = $this->findConfigFiles($config_directory);
        
        if ( ! count($config_files)) {
            throw new RuntimeException(
                "No configuration files found in directory [$config_directory]."
            );
        }
        
        foreach ($config_files as $name => $path) {
            $items = require $path;
            if ( ! is_array($items)) {
                throw new RuntimeException("Reading the [$name] config did not return an array.");
            }
            $config->extend($name, $items);
        }
        
        if ($cache_file && ! $cache_file->isCreated()) {
            $this->writeConfigCache($cache_file, $config);
        }
        
        return $config;
    }
    
    protected function createFromCache(CacheFile $cached_config) :WritableConfig
    {
        $items = json_decode($cached_config->getContents(), true, 512, JSON_THROW_ON_ERROR);
        
        if (json_last_error()) {
            throw new RuntimeException("Cant read config from cache.\n".json_last_error_msg());
        }
        
        return WritableConfig::fromArray($items);
    }
    
    protected function writeConfigCache(CacheFile $cache_file, WritableConfig $config) :void
    {
        $success = file_put_contents(
            $cache_file->realPath(),
            json_encode($config->toArray(), JSON_THROW_ON_ERROR)
        );
        
        if (false === $success) {
            throw new RuntimeException("Could not write configuration to cache file.");
        }
    }
    
    /**
     * @return array<string,string>
     */
    private function findConfigFiles(string $config_dir) :array
    {
        $files = [];
        
        foreach (Finder::create()->files()->name('*.php')->in($config_dir) as $file) {
            $files[$file->getFilenameWithoutExtension()] = $file->getRealPath();
        }
        
        ksort($files, SORT_NATURAL);
        
        return $files;
    }
    
}