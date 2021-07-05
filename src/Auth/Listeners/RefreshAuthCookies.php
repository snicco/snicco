<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Listeners;

    use BetterWP\Session\Events\SessionRegenerated;

    class RefreshAuthCookies
    {

        public function __invoke(SessionRegenerated $event)
        {
            $session = $event->session;

            $user_id = $session->userId();

            // Dont refresh auth cookies for non logged in users. This leads to deleting the auth cookies
            // wp_set_auth_cookie which can cause problems if a user tries to log in on the current request.
            if ( $user_id === 0 ) {
                return;
            }

            wp_set_auth_cookie($user_id, $session->hasRememberMeToken(), true, $session->getId() );

        }

    }