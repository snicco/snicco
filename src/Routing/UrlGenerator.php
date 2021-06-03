<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Carbon\Carbon;
    use Illuminate\Support\Arr;
    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Str;
    use WPEmerge\Support\Url;

    class UrlGenerator
    {
        use InteractsWithTime;

        private $trailing_slash = false;

        /**
         * @var RouteUrlGenerator
         */
        private $route_url;

        /**
         * @var callable
         */
        private $app_key_resolver;

        /**
         * @var callable
         */
        private $session_resolver;

        /**
         * @var callable
         */
        private $request_resolver;

        public function __construct(RouteUrlGenerator $route_url)
        {

            $this->route_url = $route_url;
        }

        public function setAppKeyResolver(callable $key_resolver)
        {

            $this->app_key_resolver = $key_resolver;
        }

        public function setSessionResolver(callable $session_resolver)
        {

            $this->session_resolver = $session_resolver;
        }

        public function setRequestResolver(callable $request_resolver)
        {

            $this->request_resolver = $request_resolver;

        }

        public function getRequest () :Request {

            return call_user_func($this->request_resolver);

        }

        public function to($path, array $query = [], $secure = true, $absolute = true) : string
        {

            if (Url::isValidAbsolute($path)) {

                return $this->formatAbsolute($this->formatTrailing($path), $absolute);

            }

            $root = $this->formatRoot($this->formatScheme($secure));

            $url = $this->format($root, $path);

            $fragment = $this->removeFragment($url);

            $url = $this->formatTrailing($url);
            $url = $this->addQueryString($url, $query);

            $url = is_null($fragment) ? $url : $url."#{$fragment}";

            return $this->formatAbsolute($url, $absolute);


        }

        public function signed( string $path, $expiration = 300 , $absolute = true, $query = [] ) : string
        {

            if (Url::isValidAbsolute($path)){
                throw new ConfigurationException('Signed urls do not work with absolute urls.');
            }

            $app_key = call_user_func($this->app_key_resolver);

            $expires = $this->availableAt($expiration);

            $query = array_merge( ['expires'=> $expires ], $query);

            $url_with_expired_query_string = $this->to($path, $query, true, $absolute);

            $signature = hash_hmac('sha256', $url_with_expired_query_string, $app_key);

            return $this->to($path, array_merge($query, ['signature'=>$signature]), true , $absolute);

        }

        public function signedRoute(string $route_name, array $arguments, $expiration = 300, bool $absolute = true) : string
        {

            $query = Arr::pull($arguments, 'query', []);

            // signed() needs a path, so dont use absolute urls here.
            $route_path = $this->toRoute($route_name, $arguments, true ,false);

            return $this->signed($route_path, $expiration, $absolute, $query);

        }

        public function hasValidSignature(Request $request, $absolute = true ) : bool
        {

            return $this->hasCorrectSignature($request, $absolute) && ! $this->signatureHasExpired($request);

        }

        public function hasValidRelativeSignature(Request $request ) : bool
        {

            return $this->hasValidSignature($request, false);

        }

        public function secure(string $path, array $query = []) : string
        {
            return $this->to($path, $query, true, true);
        }

        public function toRoute(string $name, array $arguments = [], bool $secure = true, bool $absolute = true) : string
        {

            $query = Arr::pull($arguments, 'query', []);

            $path = $this->route_url->to($name, $arguments );

            return $this->to($path, $query, $secure, $absolute);

        }

        public function previous(string $fallback = '') : string
        {

            $referrer = $this->getRequest()->getHeaderLine('referer');

            $url = $referrer ? $this->to($referrer) : $this->getPreviousUrlFromSession();

            if ($url) {
                return $this->to($url);
            } elseif ($fallback !== '') {
                return $this->to($fallback);
            }

            return $this->to('/');
        }

        public function current() : string
        {
            return $this->to($this->getRequest()->getFullUrl());
        }

        public function toLogin(string $redirect_on_login = '', bool $reauth = false) : string
        {
            return $this->to( WP::loginUrl($redirect_on_login, $reauth) );
        }

        private function getPreviousUrlFromSession() : ?string
        {
            $session = $this->getSession();

            return $session ? $session->previousUrl() : null;

        }

        private function signatureHasExpired(Request $request) : bool
        {
            $expires = $request->getQueryString('expires', null );

            if ( ! $expires ) {
                return false;
            }

            return Carbon::now()->getTimestamp() > $expires;

        }

        private function hasCorrectSignature(Request $request, $absolute = true) : bool
        {

            $url = $absolute ? $request->getUrl() : $request->getPath();

            $query_without_signature = preg_replace(
                '/(^|&)signature=[^&]+/',
                    '',
                    $request->getQueryString());

            $query_without_signature = ltrim($query_without_signature, '&');

            $signature = hash_hmac('sha256', $url.'?'.$query_without_signature, call_user_func($this->app_key_resolver));

            return hash_equals($signature, $request->getQueryString('signature', ''));

        }

        private function removeFragment(string &$uri)
        {

            // If the URI has a fragment we will move it to the end of this URI since it will
            // need to come after any query string that may be added to the URL else it is
            // not going to be available. We will remove it then append it back on here.
            if ( ! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
                $uri = preg_replace('/#.*/', '', $uri);
            }

            return $fragment;

        }

        private function addQueryString(string $uri, array $query) : string
        {

            $query = $this->buildQueryString(array_map('rawurlencode', $query));

            $uri .= $query === '' ? '' : '?'.$query;

            return $uri;

        }

        private function buildQueryString(array $query) : string
        {

            return trim(Arr::query($this->onlyStringParams($query)), '&');
        }

        private function onlyStringParams(array $parameters) : array
        {

            return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        private function format(string $root, string $path) : string
        {

            $parts = explode('#', $path);

            [$path, $fragment] = [$parts[0], isset($parts[1]) ? '#'. $parts[1] : ''];

            $path = trim($path, '/');
            $root = rtrim($root, '/');

            $path = implode('/', array_map('rawurlencode', explode('/', $path)));

            return $root.'/'. $path .$fragment;

        }

        private function formatTrailing(string $path) : string
        {

            return ($this->trailing_slash) ? rtrim($path, '/') .'/' : $path;

        }

        private function formatScheme($secure) : string
        {

            return $secure ? 'https://' : 'http://';

        }

        private function formatRoot(string $scheme, string $root = null) : string
        {

            if (is_null($root)) {

                /** @var Request $request */
                $request = $this->getRequest();

                $uri = $request->getUri();

                $root = $uri->getScheme().'://'.$uri->getHost();

            }

            $start = Str::startsWith($root, 'http://') ? 'http://' : 'https://';

            return rtrim(preg_replace('~'.$start.'~', $scheme, $root, 1), '/');
        }

        private function formatAbsolute(string $url, $absolute) : string
        {

            $host = parse_url($url)['host'];

            if ( ! $absolute) {

                return '/'.ltrim(Str::after($url, $host), '/');

            }

            return $url;

        }


        /** @todo move everything dependet on session into the StatefulRedirector */
        private function getSession() :Session
        {
            return call_user_func($this->session_resolver);
        }


    }