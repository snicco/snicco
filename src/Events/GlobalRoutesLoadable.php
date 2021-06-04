<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Application\ApplicationEvent;

    class GlobalRoutesLoadable extends ApplicationEvent
    {

        /**
         * @var ApplicationConfig
         */
        public $config;

        /**
         * @var ServerRequestInterface
         */
        public $request;

        public function __construct(ApplicationConfig $config, ServerRequestInterface $request )
        {

            $this->config = $config;
            $this->request = $request;

        }


    }