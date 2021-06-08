<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use SessionHandlerInterface;
    use WPEmerge\Http\Psr7\Request;

    interface SessionDriver extends SessionHandlerInterface
    {

        public function setRequest(Request $request);

    }