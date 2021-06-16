<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use WPEmerge\Application\ApplicationConfig;

    interface RouteRegistrarInterface
    {

        /**
         * @param  ApplicationConfig  $config
         *
         * @return bool Indicate whether a global route file was loaded successfully.
         */
        public function loadApiRoutes( ApplicationConfig $config) :bool;

        public function loadStandardRoutes( ApplicationConfig $config);

        public function loadIntoRouter() :void;
    }