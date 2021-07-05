<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Contracts;

    use WPMvc\Http\Cookie;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Session\Session;

    interface SessionManagerInterface
    {

        public function start(Request $request, int $user_id):Session;

        public function sessionCookie() :Cookie;

        public function save();

        public function collectGarbage();


    }