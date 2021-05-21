<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    class OutputBufferMiddleware extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            $response = $next($request);

            ApplicationEvent::listen('in_admin_footer', function () {

                $this->flushPhpOutputBuffer();

            });

            return $response;


        }

        public function start()
        {

            $this->cleanPhpOutputBuffer();

            ob_start();

        }

        private function flushPhpOutputBuffer()
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