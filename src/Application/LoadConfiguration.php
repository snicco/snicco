<?php


    declare(strict_types = 1);


    namespace WPEmerge\Application;

    use Exception;
    use Symfony\Component\Finder\Finder;
    use Symfony\Component\Finder\SplFileInfo;

    class LoadConfiguration
    {


        public function bootstrap(Application $app) : ApplicationConfig
        {

            $config = [];

            if (is_file($cached = $this->getCachedConfigPath($app))) {
                $config = require $cached;
                $loaded_from_cache = true;
            }

            $config = new ApplicationConfig($config);

            if ( ! isset($loaded_from_cache) ) {

                $this->loadConfigurationFromFiles($app, $config);

            }

            return $config;


        }

        private function getCachedConfigPath(Application $app ) : string
        {
            $base_path = $app->basePath();
            return $base_path . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache.php';
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


    }