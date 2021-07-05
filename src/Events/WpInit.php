<?php


    declare(strict_types = 1);


    namespace BetterWP\Events;

    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Application\Config;
    use BetterWP\Events\Event;
    use BetterWP\Http\Psr7\Request;

    class WpInit extends Event
    {

        use IsAction;

        /**
         * @var Config
         */
        public $config;

        /**
         * @var Request
         */
        public $request;

        public function __construct( Config $config, Request $request)
        {
            $this->config = $config;
            $this->request = $request;

        }

    }