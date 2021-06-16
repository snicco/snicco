<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Mail;

    use WP_User;
    use WPEmerge\Mail\Mailable;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Session;

    class ConfirmAuthMail extends Mailable
    {

        /**
         * @var WP_User
         */
        public $user;

        public $lifetime;

        public function __construct(WP_User $user, int $link_lifetime_in_sec )
        {
            $this->user = $user;
            $this->lifetime = $link_lifetime_in_sec;
        }

        public function build(Session $session, UrlGenerator $generator ) : Mailable
        {

            return $this
                ->subject('Your Email Confirmation link.')
                ->view('auth-confirm-email')
                ->with([
                    'magic_link' => $this->generateSignedUrl($session, $generator),
                ]);

        }

        public function unique() : bool
        {
            return false;
        }

        private function generateSignedUrl(Session $session, UrlGenerator $generator) : string
        {

            $arguments = [
                'user_id' => $this->user->ID,
                'query' => [
                    'intended' => $session->getIntendedUrl(),
                ],
            ];

            return $generator->signedRoute(
                'auth.confirm.magic-login',
                $arguments,
                $this->lifetime,
                true
            );

        }


    }