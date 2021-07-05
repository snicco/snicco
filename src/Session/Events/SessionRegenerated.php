<?php


    declare(strict_types = 1);


    namespace BetterWP\Session\Events;

    use BetterWP\Events\Event;
    use BetterWP\Session\Session;

    class SessionRegenerated extends Event
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