<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Events;

    use WPEmerge\Application\ApplicationEvent;

    class GenerateLogoutUrl extends ApplicationEvent
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