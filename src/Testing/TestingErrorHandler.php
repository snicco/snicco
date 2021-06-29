<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class TestingErrorHandler implements ErrorHandlerInterface
    {

        public function register()
        {
            //
        }

        public function unregister()
        {
            //
        }

        public function transformToResponse(Throwable $exception, Request $request) : ?Response
        {
            $this->fail($exception);
        }

        public function unrecoverable(Throwable $exception)
        {
            $this->fail($exception);
        }

        private function fail(Throwable $e) {

            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            throw $e;

        }

    }