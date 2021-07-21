<?php


    declare(strict_types = 1);


    namespace Snicco\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use Snicco\Events\Event;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;

    class AdminInit extends Event
    {

        use IsAction;
        use DispatchesConditionally;

        /**
         * @var Request
         */
        public $request;

        /**
         * @var string
         */
        public $hook;

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