<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\SessionStore;

    class WpLoginSessionController
    {

        /**
         * @var SessionStore
         */
        private $session;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        public function __construct(SessionStore $session, ResponseFactory $response_factory)
        {

            $this->session = $session;
            $this->response_factory = $response_factory;

        }

        /**
         *
         * A user just logged in. We migrate the session and destroy the old one.
         * Since we are in the routing flow the middleware stack takes care of sending the cookies.
         *
         * @return ResponseInterface
         */
        public function create() : ResponseInterface
        {

            $this->session->migrate(true);

            return $this->response_factory->noContent();

        }

        /**
         *
         * This route responds to GET requests to /wp-login.php.
         * However we are only interested in the ones with action=logout.
         * We can handle this at the condition level because if this route doesnt match
         * a null response will be returned which might cause the app to throw a 404 depending on
         * the user config for "must_match_web_routes".
         *
         * NOTE: This route only runs for auth users.
         *
         *
         * @param  Request  $request
         *
         * @return ResponseInterface
         */
        public function destroy (Request $request) : ResponseInterface
        {

            if ( $request->getQueryString('action', '') !== 'logout' ) {

                return $this->response_factory->noContent();

            }

            // This route ONLY runs when the wp_clear_auth_cookie() function is called.
            // This function is only called in wp-login.php
            // If a developer uses this function elsewhere we have no way knowing the used nonce
            // no we just include this quick conditional to catch obvious errors.
            if ( ! $request->getQueryString('wpnonce', null ) ) {

                return $this->response_factory->noContent();

            }

            $this->session->invalidate();
            return $this->response_factory->noContent();



        }

    }