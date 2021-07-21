<?php


    declare(strict_types = 1);


    namespace Snicco\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use Snicco\Events\Event;

    class NewLogin extends Event
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