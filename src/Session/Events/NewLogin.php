<?php


    declare(strict_types = 1);


    namespace Snicco\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use WP_User;

    class NewLogin extends Event
    {

        use IsAction;

        public string  $user_login;
        public WP_User $user;

        public function __construct(string $user_login, WP_User $user)
        {

            $this->user_login = $user_login;
            $this->user = $user;
        }

    }