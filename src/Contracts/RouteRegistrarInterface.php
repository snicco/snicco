<?php


    declare(strict_types = 1);


    namespace Snicco\Contracts;

    use Snicco\Application\Config;

    interface RouteRegistrarInterface
    {

        /**
         * @param  Config  $config
         *
         * @return bool Indicate whether a global route file was loaded successfully.
         */
        public function loadApiRoutes( Config $config) :bool;

        public function loadStandardRoutes( Config $config);

        public function loadIntoRouter() :void;
    }