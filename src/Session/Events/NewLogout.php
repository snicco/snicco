<?php


    declare(strict_types = 1);


    namespace Snicco\Session\Events;

    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;

    class NewLogout extends Event
    {

        use IsAction;

    }