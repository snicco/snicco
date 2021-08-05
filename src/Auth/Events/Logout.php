<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use Snicco\Session\Session;

    class Logout extends Event
    {

        use IsAction;

        public Session $session;
        public int $user_id;

        public function __construct(Session $session, int $user_id)
        {

            $this->session = $session;
            $this->user_id = $user_id;

            /**
             * Fires after a user is logged out.
             *
             * @since 1.5.0
             * @since 5.5.0 Added the `$user_id` parameter.
             *
             * @param int $user_id ID of the user that was logged out.
             */
            do_action( 'wp_logout', $this->session->userId() );


        }

    }