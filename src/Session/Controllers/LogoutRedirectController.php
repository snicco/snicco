<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\CsrfField;

    class LogoutRedirectController
    {

        public function __invoke(Request $request, ResponseFactory $response_factory) {

            $action = $request->getBody('action', $request->getQueryString('action') );

            if ( $action !== 'logout' ) {

                return $response_factory->null();

            }

            WP::checkAdminReferer('log-out');

            return $response_factory->signedLogout(WP::userId(), $this->redirectUrl($request));

        }

        /** @todo needs more url validation. */
        private function redirectUrl (Request $request) :string {

            if ( $redirect_to = $request->getQueryString('redirect_to') ) {

                return $redirect_to;

            }

            return add_query_arg(
                array(
                    'loggedout' => 'true',
                    'wp_lang'   => get_user_locale( wp_get_current_user()->locale ),
                ),
                wp_login_url()
            );


        }

    }