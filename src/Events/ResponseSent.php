<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Response;

    class ResponseSent extends ApplicationEvent
    {

        /**
         * @var \WPEmerge\Http\Psr7\Response
         */
        public $response;

        public function __construct( Response $response)
        {
            $this->response = $response;
        }

    }