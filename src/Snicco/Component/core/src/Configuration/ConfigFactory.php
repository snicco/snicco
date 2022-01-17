<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Configuration;

use RuntimeException;
use Symfony\Component\Finder\Finder;
use Snicco\Component\Core\Utils\PHPCacheFile;

use function is_array;
use function var_export;
use function file_put_contents;

/**
 * @interal
 */
final class ConfigFactory
{
    
    /**
     * @throws RuntimeException If no config files are found or config cache can't be written.
     */
    public function create(string $config_directory, ?PHPCacheFile $cache_file = null) :Configuration
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
        
        if ( ! isset($config_files['app'])) {
            throw new RuntimeException(
                "The [app.php] config file was not found in the config dir [$config_directory]."
            );
        }
        
        foreach ($config_files as $name => $path) {
            $items = require $path;
            if ( ! is_array($items)) {
                throw new RuntimeException("Reading the [$name] config did not return an array.");
            }
            $config->merge($name, $items);
        }
        
        if ($cache_file && ! $cache_file->isCreated()) {
            $this->writeConfigCache($cache_file, $config);
        }
        
        return $config;
    }
    
    protected function createFromCache(PHPCacheFile $cached_config) :ReadOnlyConfig
    {
        $items = $cached_config->require();
        
        if ( ! is_array($items)) {
            throw new RuntimeException(
                "The cached config did not return an array.\nUsed cache file [{$cached_config->realPath()}]."
            );
        }
        
        if ( ! isset($items['app'])) {
            throw new RuntimeException(
                "The [app] key is not present in the cached config.\nUsed cache file [{$cached_config->realpath()}]."
            );
        }
        
        return ReadOnlyConfig::fromArray($items);
    }
    
    protected function writeConfigCache(PHPCacheFile $cache_file, WritableConfig $config) :void
    {
        $success = file_put_contents(
            $cache_file->realpath(),
            '<?php return '.var_export($config->toArray(), true).';'
        );
        
        if (false === $success) {
            throw new RuntimeException(
                "Could not write configuration to cache file [{$cache_file->realpath()}]."
            );
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