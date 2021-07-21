<?php


    declare(strict_types = 1);


    namespace Snicco\Contracts;

    interface RouteUrlGenerator
    {

        public function to(string $name, array $arguments ) : string;

    }