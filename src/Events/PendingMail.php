<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use Snicco\Events\Event;
    use Snicco\Mail\Mailable;

    class PendingMail extends Event
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