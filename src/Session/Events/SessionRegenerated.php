<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Session\Session;

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