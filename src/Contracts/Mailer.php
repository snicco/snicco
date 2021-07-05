<?php


    declare(strict_types = 1);


    namespace BetterWP\Contracts;


    interface Mailer
    {

        /**
         * @param  \BetterWP\Mail\Mailable  $mail
         *
         * @return bool Whether the mail was processed correctly.
         */
        public function send ( \BetterWP\Mail\Mailable $mail ) : bool;


    }