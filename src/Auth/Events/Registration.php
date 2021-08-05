<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use WP_User;

    class Registration extends Event
    {
        use IsAction;

        public WP_User $user;

        public function __construct(WP_User $user)
        {
            $this->user = $user;
        }

    }