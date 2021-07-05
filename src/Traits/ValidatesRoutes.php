<?php


    declare(strict_types = 1);


    namespace BetterWP\Traits;

    use BetterWP\ExceptionHandling\Exceptions\ConfigurationException;
    use BetterWP\Routing\Route;

    trait ValidatesRoutes
    {

        public function validateAttributes(Route $route) {

            if ( ! $route->getAction() ) {

                throw new ConfigurationException('Tried to register a route with no attached action.');

            }

        }

    }