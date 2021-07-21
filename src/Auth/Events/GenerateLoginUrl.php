<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use Snicco\Events\Event;
    use Snicco\Support\WP;

    class GenerateLoginUrl extends Event {


        /**
         * @var string
         */
        public $redirect_to;

        /**
         * @var bool
         */
        public $force_reauth;

        public function __construct(string $url, string $redirect_to = null, bool $force_reauth = false  )
        {
            $this->redirect_to = $redirect_to ?? WP::adminUrl();
            $this->force_reauth = $force_reauth;
        }

    }