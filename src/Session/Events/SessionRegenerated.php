<?php


    declare(strict_types = 1);


    namespace BetterWP\Session\Events;

    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Session\Session;

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