<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use BetterWP\Events\Event;

    class Registration extends Event
    {
        use IsAction;

        /**
         * @var WP_User
         */
        public $user;

        public function __construct(WP_User $user)
        {

            $this->user = $user;
        }

    }