<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Psr\Http\Message\UriInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;

    class Secure extends Middleware
    {

        const HEADER = 'Strict-Transport-Security';

        /**
         * @var int One year by default
         */
        private $maxAge;

        /**
         * @var bool Whether add the preload directive or not
         */
        private $preload;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

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

        public function handle(Request $request, Delegate $next)
        {

            $uri = $request->getUri();

            if ( ! $this->isSecure($request)) {

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

            return $response->withHeader('Location', self::unParseUrl($location));


        }

        private function isSecure(Request $request) : bool
        {

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

        /**
         * Stringify a url parsed with parse_url()
         */
        private function unParseUrl(array $url) : string
        {

            $scheme = isset($url['scheme']) ? $url['scheme'].'://' : '';
            $host = $url['host'] ?? '';
            $port = isset($url['port']) ? ':'.$url['port'] : '';
            $user = $url['user'] ?? '';
            $pass = isset($url['pass']) ? ':'.$url['pass'] : '';
            $pass = ($user || $pass) ? "$pass@" : '';
            $path = $url['path'] ?? '';
            $query = isset($url['query']) ? '?'.$url['query'] : '';
            $fragment = isset($url['fragment']) ? '#'.$url['fragment'] : '';

            return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
        }


    }