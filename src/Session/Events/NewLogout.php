<?php


    declare(strict_types = 1);


    namespace BetterWP\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Application\ApplicationEvent;

    class NewLogout extends ApplicationEvent
    {

        use IsAction;

    }