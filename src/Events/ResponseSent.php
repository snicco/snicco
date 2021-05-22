<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Response;

    class ResponseSent extends ApplicationEvent
    {

        /**
         * @var Response
         */
        public $response;

        public function __construct( Response $response)
        {
            $this->response = $response;
        }

    }