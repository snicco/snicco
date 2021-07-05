<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Mail;

    use WP_User;
    use BetterWP\Mail\Mailable;
    use BetterWP\Routing\UrlGenerator;
    use BetterWP\Session\Session;

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

        public function build( UrlGenerator $generator ) : Mailable
        {

            return $this
                ->subject('Your Email Confirmation link.')
                ->view('auth-confirm-email')
                ->with([
                    'magic_link' => $this->generateSignedUrl($generator),
                ]);

        }

        public function unique() : bool
        {
            return false;
        }

        private function generateSignedUrl(UrlGenerator $generator) : string
        {

            return $generator->signedRoute(
                'auth.confirm.magic-link',
                [],
                $this->lifetime,
                true
            );

        }


    }