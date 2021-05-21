<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

    class ResponseSent extends ApplicationEvent
    {

        /**
         * @var Request
         */
        public $request;
        /**
         * @var Response
         */
        public $response;

        public function __construct(Request $request, Response $response)
        {

            $this->request = $request;
            $this->response = $response;
        }

    }