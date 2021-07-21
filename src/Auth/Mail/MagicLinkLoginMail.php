<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Mail;

    use WP_User;
    use Snicco\Support\WP;
    use Snicco\Mail\Mailable;

    class MagicLinkLoginMail extends Mailable
    {

        /** @var WP_User */
        public $user;

        public $site_name;

        public $magic_link;

        public $expires;

        /**
         * @var int
         */
        public $expiration;

        public function __construct(WP_User $user, string $magic_link, int $expiration)
        {
            $this->magic_link = $magic_link;
            $this->expiration = $expiration;
            $this->user = $user;
            $this->site_name = WP::siteName();

        }

        public function unique() : bool
        {

            return true;
        }

        public function build() : MagicLinkLoginMail
        {

            return $this
                ->subject(sprintf(__('[%s] Login Link'), WP::siteName()))
                ->view('magic-link-login-email');

        }

    }