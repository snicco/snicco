<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WP_User;
    use WPEmerge\Application\ApplicationEvent;

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