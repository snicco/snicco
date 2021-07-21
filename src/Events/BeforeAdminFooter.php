<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use Snicco\Support\WP;

    class BeforeAdminFooter extends Event
    {

        use IsAction;
        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();
        }

    }