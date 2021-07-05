<?php


    declare(strict_types = 1);


    namespace WPMvc\Application;

    use Exception;
    use Symfony\Component\Finder\Finder;
    use Symfony\Component\Finder\SplFileInfo;

    class LoadConfiguration
    {

        public function bootstrap(Application $app) : ApplicationConfig
        {

            $config = [];

            if (is_file($file = $this->getCachedConfigPath($app))) {

                $config = $this->readFromCacheFile($file);
                $loaded_from_cache = true;
            }

            $config = new ApplicationConfig($config);

            if ( ! isset($loaded_from_cache) ) {

                $this->loadConfigurationFromFiles($app, $config);

            }

            if ($config->get('app.cache_config')) {

                $this->createCacheFile($app, $config);

            }

            return $config;


        }

        private function getCachedConfigPath(Application $app ) : string
        {
            $base_path = $app->basePath();
            $ds = DIRECTORY_SEPARATOR;

            return $base_path.$ds.'bootstrap'.$ds.'cache'.$ds.'__generated::config.json';
        }

        private function loadConfigurationFromFiles(Application $app, ApplicationConfig $config)
        {

            $files = $this->getConfigurationFiles($app);

            if (! isset($files['app'])) {
                throw new Exception('Unable to load the "app" configuration file.');
            }

            foreach ($files as $key => $path) {
                $config->set($key, require $path);
            }
        }

        private function getConfigurationFiles(Application $app) : array
        {
            $files = [];

            $config_path = realpath( $app->configPath() );

            foreach (Finder::create()->files()->name('*.php')->in($config_path) as $file) {

                /** @var SplFileInfo $file */
                $files[$file->getFilenameWithoutExtension()] = $file->getRealPath();

            }

            ksort($files, SORT_NATURAL);

            return $files;
        }

        private function createCacheFile(Application $app, ApplicationConfig $config)
        {

            if ( ! is_dir($dir =$app->basePath(). DIRECTORY_SEPARATOR.'bootstrap'. DIRECTORY_SEPARATOR .'cache') ) {

                wp_mkdir_p($dir);

            }

            $success = file_put_contents(
                $dir . DIRECTORY_SEPARATOR .'__generated::config.json',
                json_encode($config->all()
                )
            );

            if ( $success === false ) {
                throw new \RuntimeException('Config could not be written to cache file');
            }

        }

        private function readFromCacheFile(string $cached)
        {

            return json_decode(file_get_contents($cached), true);

        }


    }