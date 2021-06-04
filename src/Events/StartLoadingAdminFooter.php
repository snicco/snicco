<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;

    class StartLoadingAdminFooter extends ApplicationEvent
    {

        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();

        }

    }