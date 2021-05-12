<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\FilePath;

    class RouteRegistrar
    {


        /**
         * @var \WPEmerge\Routing\Router
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

            if (WP::isAdminAjax()) {

                $this->loadRoutesGroup('ajax');

                return;
            }

            if (WP::isAdmin()) {

                $this->loadRoutesGroup('admin');

                return;
            }

            $this->loadRoutesGroup('web');

        }

        public static function loadRouteFile(string $route_file)
        {

            require $route_file;

        }

        private function loadRoutesGroup(string $group)
        {

            $dir = FilePath::addTrailingSlash($this->config->get('routing.definitions', ''));

            if ($dir === '/') {
                return;
            }

            $file = FilePath::ending($dir.$group, 'php');

            $attributes = $this->applyPreset(['middleware' => [$group]], $group);

            $this->router->group($attributes, $file);


        }

        private function applyPreset(array $attributes, string $group)
        {

            if ($group === 'admin') {

                return array_merge($attributes, [
                    'prefix' => WP::wpAdminFolder(),
                    'name' => 'admin',
                ]);

            }

            if ($group === 'ajax') {

                return array_merge($attributes, [
                    'prefix' => WP::wpAdminFolder() . DIRECTORY_SEPARATOR . 'admin-ajax.php',
                    'name' => 'ajax',
                ]);

            }

            return $attributes;

        }


    }