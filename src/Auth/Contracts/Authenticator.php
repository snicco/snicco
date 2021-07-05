<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Contracts;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Auth\Exceptions\FailedAuthenticationException;
    use WPMvc\Auth\Responses\SuccessfulLoginResponse;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Contracts\ResponsableInterface;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\Psr7\Response;
    use WPMvc\Http\ResponseFactory;

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