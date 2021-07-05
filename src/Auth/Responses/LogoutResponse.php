<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Responses;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Http\Psr7\Response;

    class LogoutResponse extends Response
    {

        public function __construct(ResponseInterface $psr7_response)
        {

            $psr7_response = $psr7_response->withAddedHeader('Expires', 'Wed, 11 Jan 1984 06:00:00 GMT')
                                           ->withAddedHeader('Cache-Control', 'no-cache, must-revalidate, max-age=0');

            parent::__construct($psr7_response);
        }

    }