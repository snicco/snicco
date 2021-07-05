<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Events\Event;
    use BetterWP\Mail\Mailable;

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