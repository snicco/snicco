<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\MagicLink;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\ExceptionHandling\Exceptions\InvalidSignatureException;

    class ValidateSignature extends Middleware
    {

        /**
         * @var string
         */
        private $type;

        /**
         * @var MagicLink
         */
        private $magic_link;

        public function __construct(MagicLink $magic_link, string $type = 'relative')
        {

            $this->type = $type;
            $this->magic_link = $magic_link;

        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            if ( $this->magic_link->hasAccessToRoute($request) ) {

                return $next($request);

            }

            $valid = $this->magic_link->hasValidSignature($request, $this->type === 'absolute');

            if ($valid) {

                $response = $next($request);

                $response = $this->magic_link->withPersistentAccessToRoute($response, $request);

                $this->magic_link->invalidate($request->fullUrl());

                return $response;

            }

            throw new InvalidSignatureException();

        }


    }