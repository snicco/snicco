<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

    class BodySent extends ApplicationEvent {


        /**
         * @var Response
         */
        public $response;

        /**
         * @var Request
         */
        public $request;

        public function __construct(Response $response, Request $request ) {

            $this->response = $response;
            $this->request = $request;

        }


	}