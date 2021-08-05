<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\WP;

    class AdminInit extends Event
    {

        use IsAction;
        use DispatchesConditionally;

        public Request $request;
        public ?string $hook;

        public function __construct(Request $request)
        {

            $this->request = $request;

            $this->hook = WP::pluginPageHook();

            if ( ! $this->hook ) {

                global $pagenow;
                $this->hook = $pagenow;

            }

        }

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax();
        }

    }