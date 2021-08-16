<?php


    declare(strict_types = 1);


    namespace Snicco\Session;

    use Slim\Csrf\Guard;
    use Snicco\Http\ResponseFactory;
    use Psr\Http\Message\ServerRequestInterface;
    use Snicco\Session\Exceptions\InvalidCsrfTokenException;

    class GuardFactory
    {

        public static function create(ResponseFactory $response_factory, $storage, int $token_strength = 40 ) : Guard
        {

            return new Guard(
                $response_factory,
                'csrf',
                $storage,
                function (ServerRequestInterface $request) {
        
                    throw new InvalidCsrfTokenException(
                        "Failed CSRF Check for path [{$request->getRequestTarget()}]",
                    );
                },
                1,
                $token_strength
            );

        }

    }