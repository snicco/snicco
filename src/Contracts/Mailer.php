<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;


    interface Mailer
    {

        /**
         * @param  \WPEmerge\Mail\Mailable  $mail
         *
         * @return bool Whether the mail was processed correctly.
         */
        public function send ( \WPEmerge\Mail\Mailable $mail ) : bool;


    }