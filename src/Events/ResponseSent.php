<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class ResponseSent extends ApplicationEvent
    {

        /**
         * @var Response
         */
        public $response;

        /**
         * @var IncomingRequest
         */
        public $request;

        public function __construct( Response $response, Request $request)
        {
            $this->response = $response;
            $this->request = $request;
        }

    }