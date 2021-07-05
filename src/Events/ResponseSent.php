<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWP\Events\Event;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;

    class ResponseSent extends Event
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