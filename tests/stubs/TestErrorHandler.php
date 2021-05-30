<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use Tests\helpers\CreatePsr17Factories;
    use Throwable;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\Http\Psr7\Response;

    class TestErrorHandler implements ErrorHandlerInterface
    {

        use CreatePsr17Factories;

        public function register()
        {
            // TODO: Implement register() method.
        }

        public function unregister()
        {
            // TODO: Implement unregister() method.
        }

        public function transformToResponse(Throwable $e) : ?Response
        {

            $code = $e instanceof HttpException ? $e->getStatusCode() : 500;
            $body = $e instanceof HttpException ? $e->getMessageForHumans() : 'Internal Server Error';
            $body = $this->psrStreamFactory()->createStream($body);

            return new Response(
                $this->psrResponseFactory()->createResponse((int) $code)
                                           ->withBody($body)
            );

        }

        public function unrecoverable(Throwable $exception)
        {
            // TODO: Implement unrecoverable() method.
        }

    }