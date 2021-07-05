<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Events;

    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Support\WP;

    class GenerateLoginUrl extends ApplicationEvent {


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