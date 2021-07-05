<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPMvc\Application\ApplicationEvent;

    class NewLogout extends ApplicationEvent
    {

        use IsAction;

    }