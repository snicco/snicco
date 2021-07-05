<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;

    class UserDeleted extends ApplicationEvent
    {

        use IsAction;

        /**
         * @var int
         */
        public $deleted_user_id;

        public function __construct(int $deleted_user_id)
        {

            $this->deleted_user_id = $deleted_user_id;
        }

    }