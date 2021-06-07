<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;

    class AdminAreaInit extends ApplicationEvent
    {

        use IsAction;
        use DispatchesConditionally;


        /**
         * @var Request
         */
        public $request;

        /**
         * @var string|null
         */
        public $hook;

        public function __construct(Request $request)
        {

            $this->request = $request;

            $this->hook = WP::pluginPageHook();

        }

        public function shouldDispatch() : bool
        {
            return WP::isAdmin() && ! WP::isAdminAjax() && $this->hook !== null;
        }




    }