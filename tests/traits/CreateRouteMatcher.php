<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use WPEmerge\Routing\FastRoute\FastRouteMatcher;

    trait CreateRouteMatcher
    {

        public function createRouteMatcher() {

            return new FastRouteMatcher();

        }

    }