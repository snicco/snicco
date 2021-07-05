<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

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