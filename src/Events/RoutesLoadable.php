<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Request;

    class RoutesLoadable extends ApplicationEvent
    {

        /**
         * @var ApplicationConfig
         */
        public $config;

        /**
         * @var Request
         */
        public $request;

        public function __construct( ApplicationConfig $config, Request $request)
        {
            $this->config = $config;
            $this->request = $request;
        }

    }