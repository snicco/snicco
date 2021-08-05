<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use Snicco\Events\Event;

    class GenerateLogoutUrl extends Event
    {

        public string $redirect_to;

        public function __construct(string $url, string $redirect_to = '/')
        {

            $this->redirect_to = $redirect_to;
        }

    }