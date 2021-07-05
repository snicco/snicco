<?php


    declare(strict_types = 1);


    namespace WPMvc\Events;

    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Mail\Mailable;

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