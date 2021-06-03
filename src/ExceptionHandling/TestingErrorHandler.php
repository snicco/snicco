<?php


    declare(strict_types = 1);


    namespace WPEmerge\ExceptionHandling;

    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Http\Psr7\Response;

    class TestingErrorHandler implements ErrorHandlerInterface
    {

        public function register()
        {
        }

        public function unregister()
        {
        }

        public function transformToResponse(Throwable $exception) : ?Response
        {
           while (ob_get_level() > 1) {
               ob_end_clean();
           }

           throw $exception;

        }

        public function unrecoverable(Throwable $exception)
        {

        }

    }