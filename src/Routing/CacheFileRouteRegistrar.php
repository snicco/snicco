<?php


    declare(strict_types = 1);


    namespace BetterWP\Routing;

    use Symfony\Component\Finder\Finder;
    use BetterWP\Application\ApplicationConfig;
    use BetterWP\Contracts\RouteRegistrarInterface;
    use BetterWP\ExceptionHandling\Exceptions\ConfigurationException;

    class CacheFileRouteRegistrar implements RouteRegistrarInterface
    {

        /**
         * @var RouteRegistrar
         */
        private $registrar;

        public function __construct(RouteRegistrar $registrar)
        {
            $this->registrar = $registrar;
        }

        public function loadApiRoutes(ApplicationConfig $config) : bool
        {
            $dir = $config->get('routing.cache_dir', '');

            if ( $this->cacheFilesCreated($dir) && count($this->registrar->apiRoutes($config)) ) {

                return true;

            }
            else {

                $this->clearRouteCache($dir);
            }

            $this->createCacheDirIfNotExists($dir);

            return $this->registrar->loadApiRoutes($config);

        }

        public function loadStandardRoutes(ApplicationConfig $config)
        {

            $dir = $config->get('routing.cache_dir', '');

            if ($this->cacheFilesCreated($dir)) {
                return;
            }

            $this->createCacheDirIfNotExists($dir);
            $this->registrar->loadStandardRoutes($config);

        }

        public function loadIntoRouter() : void
        {
            // This will do nothing for the CachedRouteCollection if the cache file exists.
            $this->registrar->loadIntoRouter();

        }

        private function createCacheDirIfNotExists (string $dir) {


            if ($dir === '') {
                throw new ConfigurationException("Route caching is enabled but no cache dir was provided.");
            }

            if ( ! is_dir($dir) ) {

                wp_mkdir_p($dir);

            }

        }

        private function cacheFilesCreated($dir) :bool {

            return is_file($dir . DIRECTORY_SEPARATOR . '__generated_route_map') &&
                   is_file($dir . DIRECTORY_SEPARATOR . '__generated_route_collection');


        }

        private function clearRouteCache(string $dir)
        {

            if ( ! is_dir( $dir ) ) {
                return;
            }

            $finder = new Finder();
            $finder->in($dir);

            if (iterator_count($finder) === 0) {
                rmdir($dir);
            }

            foreach ($finder as $file) {

                $path = $file->getRealPath();
                unlink($path);

            }

            rmdir($dir);

        }


    }