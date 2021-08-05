<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use WP_User;

    class Login extends Event
    {

        use IsAction;

        public WP_User $user;
        public bool    $remember;

        public function __construct(WP_User $user, bool $remember)
        {

            do_action('wp_login', $user->user_login, $user);

            $this->user = $user;
            $this->remember = $remember;

        }

    }