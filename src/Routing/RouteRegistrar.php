<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\FilePath;

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

        /** @todo Conditionally loading routes does not allow creating named routes in a different file. Is this any issue? What are use cases for this? */
        public function loadRoutes()
        {

            if ( WP::isAdminAjax() ) {

                $this->loadRoutesGroup('ajax');

            }

            elseif ( WP::isAdmin() ) {

                $this->loadRoutesGroup('admin');

            }

            else {

                $this->loadRoutesGroup('web');
                $this->router->createFallbackWebRoute();

            }


            $this->router->loadRoutes();

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

        private function applyPreset(array $attributes, string $group) : array
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