<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Contracts;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Auth\Exceptions\FailedAuthenticationException;
    use BetterWP\Auth\Responses\SuccessfulLoginResponse;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Contracts\ResponsableInterface;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\ResponseFactory;

    abstract class Authenticator extends Middleware
    {

        /**
         * @param  Request  $request
         * @param  Delegate $next This class can be called as a closure. $next($request)
         *
         * @return SuccessfulLoginResponse|ResponseInterface|string|array|ResponsableInterface
         *
         * SuccessfulLoginResponse if authentication was successful | Anything that can be transformed to a response otherwise
         * @see ResponseFactory::toResponse()
         *
         *
         * @throws FailedAuthenticationException
         *
         */
        abstract public function attempt ( Request $request, $next );

        public function handle(Request $request, Delegate $next) : ResponseInterface {

            return $this->response_factory->toResponse(
                $this->attempt($request, $next)
            );

        }

        protected function login(\WP_User $user, bool $remember = false ) : SuccessfulLoginResponse
        {

            return new SuccessfulLoginResponse( $this->response_factory->createResponse(), $user, $remember);

        }


    }