<?php


    declare(strict_types = 1);


    namespace BetterWP\Session\Contracts;

    use BetterWP\Http\Cookie;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Session\Session;

    interface SessionManagerInterface
    {

        public function start(Request $request, int $user_id):Session;

        public function sessionCookie() :Cookie;

        public function save();

        public function collectGarbage();


    }