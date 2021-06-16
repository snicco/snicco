<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Symfony\Component\Finder\Finder;
    use Symfony\Component\Finder\SplFileInfo;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;


    class RouteRegistrar implements RouteRegistrarInterface
    {

        /**
         * @var Router
         */
        private $router;

        public function __construct(Router $router)
        {

            $this->router = $router;

        }

        public function loadIntoRouter() : void
        {
            $this->router->loadRoutes();
        }

        public function apiRoutes (ApplicationConfig $config) : array
        {

            $dirs = Arr::wrap($config->get('routing.definitions', []));

            $finder = new Finder();
            $finder->in($dirs)->files()
                   ->name('/api\.\w+(?=\.php)/');

            return iterator_to_array($finder);

        }

        public function loadApiRoutes(ApplicationConfig $config) : bool
        {

            $files = $this->apiRoutes($config);

            if ( ! count($files) ) {
                return false;
            }

            $this->requireFiles($files, $config);

            return true;

        }

        public function loadStandardRoutes(ApplicationConfig $config)
        {

            $dirs = Arr::wrap($config->get('routing.definitions', []));

            $finder = new Finder();
            $finder->in($dirs)->files()
                   ->name('/^(?!api\.)\w+(?=\.php)/');

            $files = iterator_to_array($finder);

            if ( ! count($files) ) {
                return;
            }

            $this->requireFiles($files, $config);

            $this->router->createFallbackWebRoute();


        }

        /**
         * @param  SplFileInfo[]  $files
         * @param  ApplicationConfig  $config
         */
        private function requireFiles(array $files, ApplicationConfig $config)
        {

            $seen = [];

            foreach ( $files as $file ) {

                $name = Str::before($file->getFilename(), '.php');

                if (isset($seen[$name])) {
                    continue;
                }

                $preset = $config->get('routing.presets.'.$name, []);

                $path = $file->getRealPath();

                $this->loadRouteGroup($name, $path, $preset);

                $seen[$name] = $name;

            }

        }

        private function loadRouteGroup(string $name, string $file_path, array $preset)
        {

            $attributes = $this->applyPreset($name, $preset);


            $this->router->group($attributes, function ($router) use ($file_path) {

                require $file_path;


            });

        }

        private function applyPreset(string $group, array $preset) : array
        {


            if ($group === 'web') {

                return array_merge([
                    'middleware' => ['web'],
                ], $preset);

            }

            if ($group === 'admin') {

                return array_merge([
                    'middleware' => ['admin'],
                    'prefix' => WP::wpAdminFolder(),
                    'name' => 'admin',
                ], $preset);

            }

            if ($group === 'ajax') {

                return array_merge([
                    'middleware' => ['ajax'],
                    'prefix' => WP::wpAdminFolder().DIRECTORY_SEPARATOR.'admin-ajax.php',
                    'name' => 'ajax',
                ], $preset);

            }

            return $preset;


        }

    }