<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;

    class BeforeAdminFooter extends ApplicationEvent
    {

        use IsAction;
        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();
        }

    }