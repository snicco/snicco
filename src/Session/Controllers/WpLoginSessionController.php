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