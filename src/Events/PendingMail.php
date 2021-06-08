<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Mail\Mailable;

    class PendingMail extends ApplicationEvent
    {

        /**
         * @var Mailable
         */
        public $mail;

        public function __construct( Mailable $mail )
        {
            $this->mail = $mail;
        }


    }