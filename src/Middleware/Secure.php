<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\UriInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Support\Url;

    class Secure extends Middleware
    {

        const HEADER = 'Strict-Transport-Security';


        /**
         * @var bool Whether add the preload directive or not
         */
        private $preload;


        /**
         * @var int
         */
        private $max_age;

        /**
         * @var false
         */
        private $subdomains;

        public function __construct(ResponseFactory $response_factory, int $max_age = 31536000, $preload = false, $subdomains = false)
        {

            $this->response_factory = $response_factory;
            $this->max_age = $max_age;
            $this->preload = $preload;
            $this->subdomains = $subdomains;
        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            $uri = $request->getUri();

            if ( ! $this->isSecure($request) ) {

                // transport security header is ignored for http access.
                // @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#description

                $location = $uri->withScheme('https')->__toString();

                return $this->response_factory->redirect()
                                              ->secure($location);

            }

            $response = $next($request);

            if ( ! $response->hasHeader(self::HEADER)) {

                $response = $this->addStrictTransportPolicy($response);

            }

            if ( ! $response->isRedirect()) {

                return $response;

            }

            $location = parse_url($response->getHeaderLine('Location'));

            if ( ! isset($location['host']) || $location['host'] !== $uri->getHost()) {


                return $response;

            }

            $location['scheme'] = 'https';
            unset($location['port']);

            return $response->withHeader('Location', Url::unParseUrl($location));


        }

        private function isSecure(Request $request) : bool
        {

            if ( strtolower($request->getUri()->getScheme()) === 'https') {

                return true;

            }

            if ($request->server('HTTPS') === 'on') {

                return true;

            }

            if ($request->server('HTTP_X_FORWARDED_PROTO') === 'https') {

                return true;

            }

            if ($request->server('HTTP_X_FORWARDED_SSL') === 'on') {

                return true;

            }

            return false;


        }

        private function addStrictTransportPolicy(Response $response) : Response
        {

            $header = sprintf(
                'max-age=%d%s%s',
                $this->max_age,
                $this->subdomains ? ';includeSubDomains' : '',
                $this->preload ? ';preload' : ''
            );

            return $response->withHeader(self::HEADER, $header);

        }


    }