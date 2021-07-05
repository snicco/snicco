<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\ResponseEmitter;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Http\Responses\InvalidResponse;
    use BetterWP\Http\Responses\NullResponse;
    use BetterWP\Http\Responses\RedirectResponse;

    class OutputBufferMiddleware extends Middleware
    {

        /**
         * @var ResponseEmitter
         */
        private $emitter;

        /**
         * @var ResponseFactory
         */
        private $factory;

        /**
         * @var Response
         */
        private $retained_response;

        public function __construct(ResponseEmitter $emitter, ResponseFactory $factory)
        {
            $this->emitter = $emitter;
            $this->factory = $factory;
        }

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            $response = $next($request);

            if ( ! $this->passToKernel($response) ) {

                $this->retained_response = $response;

                return $this->factory->null();

            }

            return $response;


        }

        public function start()
        {

            $this->cleanPhpOutputBuffer();

            ob_start();

        }

        public function flush()
        {

            if ( $this->retained_response instanceof Response ) {

                $this->emitter->emit($this->retained_response);
            }

            // We made sure that our admin content sends headers and body at the correct time.
            // Now flush all output so the user does not have to wait until admin-footer-php finished
            // terminates the script.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }


        }

        private function cleanPhpOutputBuffer()
        {

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        private function passToKernel(Response $response) :bool {

            if ( php_sapi_name() === 'cli') {
                return true;
            }

            if ( $response instanceof NullResponse ) {

                return true;

            };

            if ( $response instanceof InvalidResponse ) {
                return true;
            }

            if ( $response instanceof RedirectResponse ) {
                return true;
            }

            return false;

        }

    }