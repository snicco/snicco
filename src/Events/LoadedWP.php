<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationConfig;

    class LoadedWP
    {

        use DispatchesConditionally;

        /**
         * @var ApplicationConfig
         */
        private $config;

        /**
         * @var ServerRequestInterface
         */
        private $request;

        public function __construct(ApplicationConfig $config, ServerRequestInterface $request )
        {

            $this->config = $config;
            $this->request = $request;
        }

        public function shouldDispatch() : bool
        {

            return $this->config->get('always_run_middleware', false );

        }

        public function payload() {

            return $this->request;

        }

    }