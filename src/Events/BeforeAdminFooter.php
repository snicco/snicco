<?php


    declare(strict_types = 1);


    namespace WPMvc\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Support\WP;

    class BeforeAdminFooter extends ApplicationEvent
    {

        use IsAction;
        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();
        }

    }