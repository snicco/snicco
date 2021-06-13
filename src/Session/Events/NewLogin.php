<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;

    class NewLogin extends ApplicationEvent
    {

        use IsAction;

        public function __construct()
        {

            $foo = 'bar';

        }

    }