<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Symfony\Component\Finder\Finder;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\FilePath;
    use WPEmerge\Support\Str;

    class RouteRegistrar
    {

        /**
         * @var Router
         */
        private $router;

        /**
         * @var ApplicationConfig
         */
        private $config;

        public function __construct(Router $router, ApplicationConfig $config)
        {

            $this->router = $router;
            $this->config = $config;

        }

        public function loadRoutes()
        {

            $dirs = $this->config->get('routing.definitions', []);

            if ($dirs === []) {
                return;
            }

            $finder = new Finder();
            $finder->in($dirs)->files()->name('*.php');

            $seen = [];

            foreach ($finder as $file) {

                $name = Str::before($file->getFilename(), '.php');

                if ( isset( $seen[$name] ) ) {
                    continue;
                }

                $preset = $this->config->get('routing.presets.'.$name, []);

                $path = $file->getRealPath();

                $this->loadRouteGroup($name, $path, $preset);

                $seen[$name] = $name;

            }

            $this->router->createFallbackWebRoute();

            $this->router->loadRoutes();

        }

        public static function loadRouteFile(string $route_file)
        {

            require $route_file;

        }

        private function loadRouteGroup(string $name, string $file_path, array $preset)
        {

            $attributes = $this->applyPreset($name, $preset);

            $this->router->group($attributes, $file_path);

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