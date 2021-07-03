<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Mail;

    use WPEmerge\Support\WP;
    use WPEmerge\Mail\Mailable;

    class ConfirmRegistrationEmail extends Mailable
    {


        /**
         * @var string
         */
        public $magic_link;

        public function __construct(string $magic_link)
        {

            $this->magic_link = $magic_link;
        }

        public function build() : ConfirmRegistrationEmail
        {

            return $this->subject(sprintf("Confirm your registration at %s", WP::siteName()))
                        ->view('confirm-registration-email');

        }

        public function unique() : bool
        {

            return true;
        }

    }