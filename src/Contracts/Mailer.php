<?php


    declare(strict_types = 1);


    namespace WPMvc\Contracts;


    interface Mailer
    {

        /**
         * @param  \WPMvc\Mail\Mailable  $mail
         *
         * @return bool Whether the mail was processed correctly.
         */
        public function send ( \WPMvc\Mail\Mailable $mail ) : bool;


    }