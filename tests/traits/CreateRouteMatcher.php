<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\RouteCompiler;

    trait CreateRouteMatcher
    {

        public function createRouteMatcher(RouteCompiler $compiler) {

            return new FastRouteMatcher($compiler);

        }

    }