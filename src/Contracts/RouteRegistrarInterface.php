<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Application\ApplicationConfig;

    interface RouteRegistrarInterface
    {

        /**
         * @param  ApplicationConfig  $config
         *
         * @return bool Indicate wheter a global route file was loaded successfully.
         */
        public function loadGlobalRoutes( ApplicationConfig $config) :bool;

        public function loadStandardRoutes( ApplicationConfig $config);

    }