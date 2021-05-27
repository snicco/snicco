<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Psr\Http\Message\ServerRequestInterface;
    use SessionHandlerInterface;

    interface SessionHandler extends SessionHandlerInterface
    {

        public function setRequest(ServerRequestInterface $request);

    }