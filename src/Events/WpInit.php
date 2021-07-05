<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Application\ApplicationConfig;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Http\Psr7\Request;

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