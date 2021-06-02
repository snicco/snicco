<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Psr7\Request;

    /**
     * Fires on the wp_login and wp_logout hooks.
     */
    class WpLoginAction extends ApplicationEvent
    {

        use DispatchesConditionally;

        /**
         * @var Request
         */
        private $request;
        /**
         * @var bool
         */
        private $sessions_enabled;

        public function __construct(Request $request, bool $sessions_enabled = false )
        {
            $this->request = $request;
            $this->sessions_enabled = $sessions_enabled;
        }

        public function shouldDispatch() : bool
        {
            return $this->sessions_enabled;
        }

        public function payload () : IncomingWebRequest
        {

            return new IncomingWebRequest('', $this->request);

        }

    }