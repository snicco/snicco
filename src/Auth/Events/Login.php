<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use Snicco\Events\Event;

    class Login extends Event
    {

        use IsAction;

        /**
         * @var WP_User
         */
        public $user;

        /**
         * @var bool
         */
        public $remember;

        public function __construct(WP_User $user, bool $remember)
        {

            do_action('wp_login', $user->user_login, $user);

            $this->user = $user;
            $this->remember = $remember;

        }

    }