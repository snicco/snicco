<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;

    class GenerateLoginUrl extends ApplicationEvent {


        /**
         * @var string
         */
        public $redirect_to;

        public function __construct(string $url, string $redirect_to = null )
        {

            $this->redirect_to = $redirect_to ?? WP::adminUrl();
        }

    }