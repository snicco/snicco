<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use BetterWP\Application\ApplicationEvent;

    class Registration extends ApplicationEvent
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