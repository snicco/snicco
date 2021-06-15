<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\InvalidResponse;
    use WPEmerge\Http\Responses\NullResponse;
    use WPEmerge\Http\Responses\RedirectResponse;

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

        public function handle(Request $request, Delegate $next)
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