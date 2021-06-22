<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionDriver;

    class Logout extends ApplicationEvent
    {

        use IsAction;

        /**
         * @var Session
         */
        public $session;

        public function __construct(Session $session)
        {

            $this->session = $session;
            $this->session->invalidate();
            wp_clear_auth_cookie();

            /**
             * Fires after a user is logged out.
             *
             * @since 1.5.0
             * @since 5.5.0 Added the `$user_id` parameter.
             *
             * @param int $user_id ID of the user that was logged out.
             */
            do_action( 'wp_logout', $this->session->userId() );

            $this->session->setUserId(0);
            wp_set_current_user( 0 );

        }

    }