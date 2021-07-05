<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Events;

    use BetterWP\Events\Event;

    class GenerateLogoutUrl extends Event
    {

        /**
         * @var string
         */
        public $redirect_to;

        public function __construct(string $url, string $redirect_to = '/')
        {

            $this->redirect_to = $redirect_to;
        }

    }