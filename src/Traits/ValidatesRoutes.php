<?php


    declare(strict_types = 1);


    namespace WPEmerge\Traits;

    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Routing\Route;

    trait ValidatesRoutes
    {

        public function validateAttributes(Route $route) {

            if ( ! $route->getAction() ) {

                throw new ConfigurationException('Tried to register a route with no attached action.');

            }

        }

    }