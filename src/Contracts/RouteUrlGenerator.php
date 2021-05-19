<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    interface RouteUrlGenerator
    {

        public function to(string $name, array $arguments ) : string;

    }