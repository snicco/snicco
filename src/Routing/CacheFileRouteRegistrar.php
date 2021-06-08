<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Contracts\RouteRegistrarInterface;

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

        public function loadGlobalRoutes(ApplicationConfig $config) : bool
        {

            $dir = $config->get('routing.cache.dir', '');

            if ( ! is_file($dir . DIRECTORY_SEPARATOR . '__generated_global.routes.php') ) {



            }


        }

        public function loadStandardRoutes(ApplicationConfig $config)
        {
            // TODO: Implement loadStandardRoutes() method.
        }

    }