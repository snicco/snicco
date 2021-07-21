<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;

    class UserDeleted extends Event
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