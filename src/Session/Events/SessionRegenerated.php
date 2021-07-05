<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Events;

    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Session\Session;

    class SessionRegenerated extends ApplicationEvent
    {

        /**
         * @var Session
         */
        public $session;

        public function __construct(Session $session)
        {
            $this->session = $session;
        }

    }