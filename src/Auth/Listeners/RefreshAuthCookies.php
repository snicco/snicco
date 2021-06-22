<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Listeners;

    use WPEmerge\Session\Events\SessionRegenerated;

    class RefreshAuthCookies
    {

        public function __invoke(SessionRegenerated $event)
        {
            $session = $event->session;
            wp_set_auth_cookie($session->userId(), $session->hasRememberMeToken(), true, $session->getId() );
        }

    }