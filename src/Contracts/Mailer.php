<?php


    declare(strict_types = 1);


    namespace Snicco\Contracts;


    interface Mailer
    {

        /**
         * @param  \Snicco\Mail\Mailable  $mail
         *
         * @return bool Whether the mail was processed correctly.
         */
        public function send ( \Snicco\Mail\Mailable $mail ) : bool;


    }