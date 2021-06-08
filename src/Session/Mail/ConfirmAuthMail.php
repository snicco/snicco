<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Mail;

    use WP_User;
    use WPEmerge\Mail\Mailable;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Url;

    class ConfirmAuthMail extends Mailable
    {

        /**
         * @var WP_User
         */
        public $user;

        /**
         * @var Session
         */
        private $session;
        /**
         * @var UrlGenerator
         */
        private $url_generator;

        private $link_lifetime_in_sec;

        public function __construct(WP_User $user, Session $session, UrlGenerator $generator, $link_lifetime_in_sec )
        {
            $this->user = $user;
            $this->session = $session;
            $this->url_generator = $generator;
            $this->link_lifetime_in_sec = $link_lifetime_in_sec;
        }

        public function build() : Mailable
        {

            return $this
                ->subject('Your Email Confirmation link.')
                ->view('auth-confirm-email')
                ->with([
                    'magic_link' => $this->generateSignedUrl(),
                    'lifetime' => $this->link_lifetime_in_sec,
                ]);

        }

        public function unique() : bool
        {
            return false;
        }


        private function generateSignedUrl() : string
        {

            $arguments = [
                'user_id' => $this->user->ID,
                'query' => [
                    'intended' => $this->session->getIntendedUrl(),
                ],
            ];

            return $this->url_generator->signedRoute(
                'auth.confirm.magic-login',
                $arguments,
                $this->link_lifetime_in_sec
            );

        }


    }