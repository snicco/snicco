<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Listeners;

    use BetterWP\Auth\Events\GenerateLoginUrl;
    use BetterWP\Auth\Events\GenerateLogoutUrl;
    use BetterWP\Events\ResponseSent;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Redirector;
    use BetterWP\Http\ResponseEmitter;
    use BetterWP\Routing\UrlGenerator;

    class WpLoginLinkGenerator
    {

        /** NOTE: Wordpress always returns these as absolute urls so lets stay compatible */
        public function loginUrl(GenerateLoginUrl $event, UrlGenerator $url) : string
        {

            $query = [];

            $query['redirect_to'] = $event->redirect_to !== ''
                ? $event->redirect_to
                : $url->toRoute('dashboard');

            if ($event->force_reauth) {
                $query['reauth'] = 'yes';
            }

            return $url->toRoute('auth.login', [
                'query' => $query,
            ], true, true);

        }

        /** NOTE: Wordpress always returns these as absolute urls so lets stay compatible */
        public function logoutUrl(GenerateLogoutUrl $event, UrlGenerator $url) : string
        {

            $redirect = $event->redirect_to;

            $url = $url->signedLogout(WP::userId(), $redirect, 3600, true );

            return esc_html($url);

        }

    }