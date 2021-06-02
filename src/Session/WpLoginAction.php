<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Psr7\Request;

    class WpLoginAction extends ApplicationEvent
    {

        use DispatchesConditionally;

        /**
         * @var Request
         */
        private $request;

        public function __construct(Request $request)
        {

            $this->request = $request;
        }

        public function shouldDispatch() : bool
        {

            if ($this->request->isPost()) {
                return true;
            }

            if ( ! $this->request->isGet() ) {
                return false;
            }

            if ( $this->request->getQueryString('action') !== 'logout' ) {
                return false;
            }

            if ( ! in_array('wpnonce', $this->request->getQueryParams() ) ) {
                return false;
            }

            return true;


        }

        public function payload() : IncomingWebRequest
        {

            return new IncomingWebRequest('', $this->request);

        }

    }