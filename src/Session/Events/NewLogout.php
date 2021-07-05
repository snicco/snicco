<?php


    declare(strict_types = 1);


    namespace BetterWP\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Events\Event;

    class NewLogout extends Event
    {

        use IsAction;

    }