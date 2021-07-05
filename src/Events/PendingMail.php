<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Mail\Mailable;

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