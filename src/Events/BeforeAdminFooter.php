<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Support\WP;

    class BeforeAdminFooter extends ApplicationEvent
    {

        use IsAction;
        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();
        }

    }