<?php


    declare(strict_types = 1);


    namespace Snicco\Contracts;


    use Snicco\Mail\Mailable;

    interface Mailer
    {

        /**
         * @param  Mailable  $mail
         *
         * @return bool Whether the mail was processed correctly.
         */
        public function send ( Mailable $mail ) : bool;


    }