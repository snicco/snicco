<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Exceptions;

    use Throwable;
    use WPEmerge\ExceptionHandling\Exceptions\HttpException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\ResponseFactory;

    class InvalidSignatureException extends HttpException
    {

        public function __construct(int $status_code = 403, ?string $message_for_humans = 'You cant access this page.', Throwable $previous = null, ?int $code = 0)
        {

            parent::__construct($status_code, $message_for_humans, $previous, $code);
        }

        public function render(ResponseFactory $response_factory ) {

            return $response_factory->redirect(403)->to(WP::loginUrl('', true));

        }


    }