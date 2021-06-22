<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Contracts;

    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Session;

    interface SessionManagerInterface
    {

        public function start(Request $request, int $user_id):Session;

        public function sessionCookie() :Cookie;

        public function save();

        public function collectGarbage();


    }