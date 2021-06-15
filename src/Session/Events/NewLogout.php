<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;

    class NewLogout extends ApplicationEvent
    {

        use IsAction;

    }