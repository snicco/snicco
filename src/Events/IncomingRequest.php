<?php


	declare( strict_types = 1 );


	namespace WPMvc\Events;

	use WPMvc\Application\ApplicationEvent;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Support\Str;

    class IncomingRequest extends ApplicationEvent {


		/**
		 * @var Request
		 */
		public $request;


        /**
         * @var bool
         */
        protected $has_matching_route = false;

        public function __construct( Request $request) {

			$this->request = $request;

		}

        public function matchedRoute()
        {

            $this->has_matching_route = true;

        }


    }