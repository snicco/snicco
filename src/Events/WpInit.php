<?php


    declare(strict_types = 1);


    namespace WPMvc\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPMvc\Application\ApplicationConfig;
    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Http\Psr7\Request;

    class WpInit extends ApplicationEvent
    {

        use IsAction;

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