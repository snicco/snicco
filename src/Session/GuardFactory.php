<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Slim\Csrf\Guard;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Exceptions\InvalidCsrfTokenException;

    class GuardFactory
    {

        public static function create(ResponseFactory $response_factory, $storage, int $token_strength = 40 ) : Guard
        {

            return new Guard(
                $response_factory,
                'csrf',
                $storage,
                function () {

                    throw new InvalidCsrfTokenException(
                        'The Link you followed expired.',
                    );
                },
                1,
                $token_strength
            );

        }

    }