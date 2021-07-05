<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use WPMvc\Application\ApplicationEvent;

    class NewLogin extends ApplicationEvent
    {

        use IsAction;

        /**
         * @var string
         */
        public $user_login;

        /**
         * @var WP_User
         *
         */
        public $user;

        public function __construct(string $user_login, WP_User $user)
        {
            $this->user_login = $user_login;
            $this->user = $user;
        }

    }