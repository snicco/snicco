<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Snicco\Routing\FastRoute\FastRouteMatcher;

    /**
     * @internal
     */
    trait CreateRouteMatcher
    {

        public function createRouteMatcher() : FastRouteMatcher
        {

            return new FastRouteMatcher();

        }

    }