<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
    use Respect\Stringifier\Quoters\CodeQuoter;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Url;

    abstract class AbstractRedirector
    {

        /**
         * @var UrlGenerator
         */
        protected $generator;

        /**
         * @var Psr17ResponseFactory
         */
        protected $response_factory;

        public function __construct(UrlGenerator $url_generator, Psr17ResponseFactory $response_factory)
        {
            $this->generator = $url_generator;
            $this->response_factory = $response_factory;
        }

        abstract public function createRedirectResponse ( string $path, int $status_code = 302 ) : RedirectResponse;

        public function home($status = 302, bool $secure = true, bool $absolute = true) : RedirectResponse
        {
            return $this->to($this->generator->toRoute('home', [], $secure, $absolute), $status);
        }

        public function to (string $path, int $status = 302, array $query = [], bool $secure = true, bool $absolute = true ) : RedirectResponse
        {

            return $this->createRedirectResponse(
                $this->generator->to($path, $query, $secure, $absolute),
                $status
            );

        }

        public function toRoute(string $name, int $status =302, array $arguments = [], bool $secure = true, bool $absolute = true ) : RedirectResponse
        {

            return $this->to(
                $this->generator->toRoute($name, $arguments, $secure, $absolute),
                $status
            );

        }

        public function toSignedRoute( string $name, array $arguments = [], int $status = 302, int $expiration = 300, bool $absolute = true) : RedirectResponse
        {
            return $this->to(
                $this->generator->signedRoute($name, $arguments, $expiration, $absolute),
                $status
            );

        }

        public function signedLogout ( int $user_id, string $redirect_on_logout = '/',  int $status = 302  ) : RedirectResponse
        {

           return $this->to(
               $this->generator->signedLogout($user_id, $redirect_on_logout),
               $status
           );

        }

        public function toTemporarySignedRoute(string $name, int $expiration = 300, $arguments = [], $status = 302, bool $absolute = true ) :RedirectResponse
        {
            return $this->toSignedRoute($name, $arguments, $status, $expiration, $absolute);
        }

        public function secure(string $path, int $status = 302, array $query = []) : RedirectResponse
        {
            return $this->to($path, $status, $query);
        }

        public function guest (string $path, $status = 302, array $query = [], bool $secure = true, bool $absolute = true ) {

            throw new \LogicException('The Redirector::guest method can only be used when sessions are enabled in the config');

        }

        public function toLogin(string $redirect_on_login = '', bool $reauth = false , int $status_code = 302) : RedirectResponse
        {
            return $this->to($this->generator->toLogin($redirect_on_login, $reauth), $status_code);
        }

        /**
         * Create a new redirect response to an external URL (no validation).
         */
        public function away($path, $status = 302) : RedirectResponse
        {
            return $this->createRedirectResponse($path, $status);
        }

        public function refresh(int $status = 302) : RedirectResponse
        {
            return $this->createRedirectResponse($this->generator->current(), $status);
        }

        public function back(  int $status = 302, string $fallback = '') : RedirectResponse
        {

            $previous_url = $this->generator->back($fallback);

            return $this->createRedirectResponse($previous_url, $status);

        }

        public function intended (Request $request, string $fallback = '', int $status = 302) : RedirectResponse
        {

            $from_query = rawurldecode($request->query('intended', ''));

            if (Url::isValidAbsolute( $from_query ) ) {
                return $this->to($from_query, $status);
            }

            if ( $fallback !== '' ) {
                return $this->to($fallback, $status);
            }

            return $this->to('/', $status);

        }

        public function previous(Request $request, int $status = 302, string $fallback = '') : RedirectResponse
        {
            return $this->back($status, $fallback);
        }

        protected function validateStatusCode (int $status_code){

            $valid = in_array($status_code, [201, 301, 302, 303, 304,  307, 308]);

            if ( ! $valid ) {
                throw new \LogicException("Status code [{$status_code} is not valid for redirects.]");
            }

        }

    }