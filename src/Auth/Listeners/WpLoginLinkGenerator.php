<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Listeners;

    use WPMvc\Auth\Events\GenerateLoginUrl;
    use WPMvc\Auth\Events\GenerateLogoutUrl;
    use WPMvc\Events\ResponseSent;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Redirector;
    use WPMvc\Http\ResponseEmitter;
    use WPMvc\Routing\UrlGenerator;

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