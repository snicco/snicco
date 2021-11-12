<?php

declare(strict_types=1);

namespace Snicco\Bootstrap;

use Exception;
use RuntimeException;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function wp_mkdir_p;

class LoadConfiguration implements Bootstrapper
{
    
    public function bootstrap(Application $app) :void
    {
        if (is_file($file = $app->configCachePath())) {
            $items = $this->readFromCacheFile($file);
            $app->config()->seedFromCache($items);
            return;
        }
        
        if ( ! isset($loaded_from_cache)) {
            $this->loadConfigurationFromFiles($app);
        }
        
        if ($app->config('app.cache_config')) {
            $this->createCacheFile($app);
        }
    }
    
    private function readFromCacheFile(string $cached)
    {
        return json_decode(file_get_contents($cached), true);
    }
    
    private function loadConfigurationFromFiles(Application $app)
    {
        $files = $this->getConfigurationFiles($app);
        
        if ( ! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }
        
        foreach ($files as $key => $path) {
            $app->config()->extend($key, require $path);
        }
    }
    
    private function getConfigurationFiles(Application $app) :array
    {
        $files = [];
        
        $config_path = realpath($app->configPath());
        
        foreach (Finder::create()->files()->name('*.php')->in($config_path) as $file) {
            /** @var SplFileInfo $file */
            $files[$file->getFilenameWithoutExtension()] = $file->getRealPath();
        }
        
        ksort($files, SORT_NATURAL);
        
        return $files;
    }
    
    private function createCacheFile(Application $app)
    {
        if ( ! is_dir(
            $dir = $app->basePath().DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'
        )) {
            wp_mkdir_p($dir);
        }
        
        $success = file_put_contents(
            $dir.DIRECTORY_SEPARATOR.'__generated::config.json',
            json_encode(
                $app->config()->all()
            )
        );
        
        if ($success === false) {
            throw new RuntimeException('Config could not be written to cache file.');
        }
    }
    
    private function getCachedConfigPath(Application $app) :string
    {
        $base_path = $app->basePath();
        $ds = DIRECTORY_SEPARATOR;
        
        return $base_path.$ds.'bootstrap'.$ds.'cache'.$ds.'__generated::config.json';
    }
    
}