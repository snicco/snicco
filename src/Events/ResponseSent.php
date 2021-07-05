<?php


    declare(strict_types = 1);


    namespace WPMvc\Events;

    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Psr7\Response;

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