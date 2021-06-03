<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Carbon\Carbon;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Session;

    class WpLoginSessionController
    {

        /**
         * @var Session
         */
        private $session;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var int
         */
        private $auth_confirm_lifetime_in_minutes;

        /**
         * @var bool
         */
        private $confirm_on_login;

        public function __construct(Session $session, ResponseFactory $response_factory, int $auth_confirm_lifetime = 180, bool $confirm_on_login = true )
        {

            $this->session = $session;
            $this->response_factory = $response_factory;
            $this->auth_confirm_lifetime_in_minutes = $auth_confirm_lifetime;
            $this->confirm_on_login = $confirm_on_login;

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

            if ( $this->confirm_on_login ) {

                $this->session->put(
                    'auth.confirm.until',
                    Carbon::now()->addMinutes($this->auth_confirm_lifetime_in_minutes)->getTimestamp()
                );

            }


            return $this->response_factory->noContent();

        }

        /**
         *
         *
         * NOTE: This route only runs on the wp_logout hook.
         * This route will NOT run for random GET requests to wp_logout since these
         * wont trigger the template include filter which is only triggered in wp_blog_header.php
         *
         *
         * @return ResponseInterface
         */
        public function destroy () : ResponseInterface
        {

            $this->session->invalidate();

            return $this->response_factory->noContent();


        }

    }