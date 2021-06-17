<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Listeners;

    use WPEmerge\Auth\Events\GenerateLoginUrl;
    use WPEmerge\Auth\Events\GenerateLogoutUrl;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Routing\UrlGenerator;

    class WpLoginRedirectManager
    {

        public function redirect(Request $request, Redirector $redirector, ResponseEmitter $emitter) {

            if ( $request->loadingScript() === 'wp-login.php' && $request->isGet() ) {

                $response = $redirector->toRoute('login', 301);

                $emitter->emit($response);

                ResponseSent::dispatch([$response, $request]);

            }

        }

        /** NOTE: Wordpress always returns these as absolute urls so lets stay compatible */
        public function loginUrl (GenerateLoginUrl $event, UrlGenerator $url) : string
        {

            $query = [];

            if ( $event->redirect_to !== '' ) {
                $query['redirect_to'] = $event->redirect_to;
            }

            return $url->toRoute('login', [
                'query' => $query
            ], true , true );

        }

        /** NOTE: Wordpress always returns these as absolute urls so lets stay compatible */
        public function logoutUrl (GenerateLogoutUrl $event, UrlGenerator $url) : string
        {

                $redirect = $event->redirect_to;

                $url = $url->signedLogout(WP::userId(), $redirect );

                return esc_html( $url );

        }

    }