<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    class OutputBufferMiddleware extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            return $next($request);

        }

        public function start()
        {

            $this->cleanPhpOutputBuffer();

            ob_start();

        }

        public function flush()
        {

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


    }