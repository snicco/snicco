<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
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

        public function refresh(int $status = 302) : RedirectResponse
        {
            return $this->createRedirectResponse($this->generator->current(), $status);
        }

        public function back( int $status = 302, string $fallback = '') : RedirectResponse
        {

            $previous_url = $this->generator->back($fallback);

            return $this->createRedirectResponse($previous_url, $status);

        }

        public function guest( int $status_code = 302 ) : RedirectResponse
        {
            return $this->createRedirectResponse( WP::loginUrl() , $status_code);
        }

        public function intended (Request $request, string $fallback = '', int $status = 302) : RedirectResponse
        {

            $from_query = rawurldecode($request->getQueryString('intended', ''));

            if (Url::isValidAbsolute( $from_query ) ) {
                return $this->to($from_query, $status);
            }

            if ( $fallback !== '' ) {
                return $this->to($fallback, $status);
            }

            return $this->to('/', $status);

        }

        protected function validateStatusCode (int $status_code){

            $valid = in_array($status_code, [201, 301, 302, 303, 307, 308]);

            if ( ! $valid ) {
                throw new \LogicException("Status code [{$status_code} is not valid for redirects.]");
            }

        }

    }