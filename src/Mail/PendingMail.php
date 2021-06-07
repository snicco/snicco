<?php


    declare(strict_types = 1);


    namespace WPEmerge\Mail;

    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use WPEmerge\Contracts\Mailable;
    use WPEmerge\Events\SendMailEvent;

    class PendingMail
    {

        /**
         * @var WordpressDispatcher
         */
        private $dispatcher;

        public function __construct( Dispatcher $dispatcher )
        {
            $this->dispatcher = $dispatcher;
        }

        public function send( Mailable $mail )
        {
            $this->dispatcher->dispatch( new SendMailEvent($mail) );
        }

    }