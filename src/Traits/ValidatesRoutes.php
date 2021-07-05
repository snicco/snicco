<?php


    declare(strict_types = 1);


    namespace WPMvc\Traits;

    use WPMvc\ExceptionHandling\Exceptions\ConfigurationException;
    use WPMvc\Routing\Route;

    trait ValidatesRoutes
    {

        public function validateAttributes(Route $route) {

            if ( ! $route->getAction() ) {

                throw new ConfigurationException('Tried to register a route with no attached action.');

            }

        }

    }