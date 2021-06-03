<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Psr\Http\Message\ServerRequestInterface;
    use SessionHandlerInterface;

    interface SessionDriver extends SessionHandlerInterface
    {

        public function setRequest(ServerRequestInterface $request);

    }