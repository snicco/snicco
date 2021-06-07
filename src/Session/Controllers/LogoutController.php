<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use WPEmerge\Http\Psr7\Request;

    class LogoutController
    {

        public function __invoke(Request $request)
        {

            return 'logout';

        }

    }