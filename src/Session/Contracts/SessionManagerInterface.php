<?php


    declare(strict_types = 1);


    namespace Snicco\Session\Contracts;

    use Snicco\Http\Cookie;
    use Snicco\Http\Psr7\Request;
    use Snicco\Session\Session;

    interface SessionManagerInterface
    {

        public function start(Request $request, int $user_id):Session;

        public function sessionCookie() :Cookie;

        public function save();

        public function collectGarbage();


    }