<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Contracts\Mailable;

    class SendMailEvent
    {

        /**
         * @var Mailable
         */
        public $mail;

        public function __construct(Mailable $mail)
        {
            $this->mail = $mail;
        }
    }