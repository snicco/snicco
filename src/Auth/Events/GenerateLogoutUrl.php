<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Events;

    use WPMvc\Application\ApplicationEvent;

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