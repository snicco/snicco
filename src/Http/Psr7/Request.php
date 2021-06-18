<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http\Psr7;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Routing\RoutingResult;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;
    use WPEmerge\Support\VariableBag;
    use WPEmerge\Validation\Validator;

    class Request implements ServerRequestInterface
    {

        use ImplementsPsr7Request;
        use InspectsRequest;
        use InteractsWithInput;

        public function __construct(ServerRequestInterface $psr_request)
        {

            $this->psr_request = $psr_request;

        }

        public function withType(string $type)
        {

            return $this->withAttribute('type', $type);

        }

        /**
         * This method stores the URI that is used for matching against the routes
         * inside the AbstractRouteCollection. This URI is modified inside the CORE Middleware
         * for wp-admin and admin-ajax routes
         * to provide a more friendly api for matching these type of routes.
         *
         * For admin routes the [page] query parameter is appended to the wp-admin url.
         * For ajax routes the [action] query parameter is appended to the admin-ajax url.
         *
         * This is stored in an additional attribute to not tamper with the "real" requested URL.
         *
         * This URI shall not be used anymore BESIDES FOR MATCHING A ROUTE.
         *
         */
        public function withRoutingUri(UriInterface $uri)
        {

            return $this->withAttribute('routing.uri', $uri);
        }

        public function withRoutingResult(RoutingResult $routing_result)
        {

            return $this->withAttribute('routing.result', $routing_result);

        }

        public function filtersWpQuery(?bool $set = null)
        {

            if ($set) {

                return $this->withAttribute('filtered_wp_query', true);

            }

            return $this->getAttribute('filtered_wp_query', false);

        }

        public function withCookies(array $cookies)
        {

            return $this->withAttribute('cookies', new VariableBag($cookies));

        }

        public function withSession(Session $session_store)
        {

            return $this->withAttribute('session', $session_store);

        }

        public function withUser(int $user_id)
        {
            return $this->withAttribute('_current_user_id', $user_id);

        }

        public function withValidator(Validator $v)
        {

            return $this->withAttribute('_validator', $v);
        }

        public function validator() : Validator
        {
            $v = $this->getAttribute('_validator');

            if ( ! $v instanceof Validator) {
                throw new \RuntimeException('A validator instance has not been set on the request.');
            }

            return $v;

        }

        /**
         * @param  bool  $by_id
         *
         * @return \WP_User|int
         */
        public function user(bool $by_id = false )
        {

            $user = $this->getAttribute('_current_user_id');

            return $by_id ? $user->ID : $user;

        }

        public function userAgent() {

            return substr($this->getHeaderLine('User-Agent'), 0, 500);

        }

        public function path() : string
        {
            return $this->getUri()->getPath();
        }

        public function fullPath() : string
        {

            $fragment = $this->getUri()->getFragment();

            return ($fragment !== '')
                ? $this->getRequestTarget() . '#' .$fragment
                : $this->getRequestTarget();


        }

        public function url() : string
        {

            return preg_replace('/\?.*/', '', $this->getUri());

        }

        public function fullUrl() : string
        {

            return $this->getUri()->__toString();

        }

        /**
         * @internal
         */
        public function routingPath() : string
        {

            $uri = $this->getAttribute('routing.uri');

            /** @var UriInterface $uri */
            $uri = $uri ?? $this->getUri();

            return $uri->getPath();

        }

        /**
         * @internal
         */
        public function isRouteable() : bool
        {

            $script = $this->loadingScript();

            // All public web requests
            if ($script === 'index.php') {

                return true;

            }

            // A request to the admin dashboard. We can catch that within admin_init
            if (Str::contains($script, $this->getAttribute('_wp_admin_folder'))) {

                return true;

            }

            // Not routeable for web/ajax/admin routes because the correct hooks wont be triggered
            // by WordPress. eg /wp-login.php
            // These requests can only be "routed" by using the init hook.
            return false;

        }

        public function type() : string
        {

            return $this->getAttribute('type', '');

        }

        public function routingResult() : RoutingResult
        {

            return $this->getAttribute('routing.result', new RoutingResult(null, []));

        }

        public function cookies() : VariableBag
        {

            /** @var VariableBag $bag */
            $bag = $this->getAttribute('cookies', new VariableBag());

            if ( $bag->all() === [] ) {

                $cookies = Cookies::parseHeader($this->getHeader('Cookie'));

                $bag->add($cookies);

            }

            return $bag;


        }

        public function session() : Session
        {

            if ( ! $this->hasSession() ) {
                throw new \RuntimeException('A session has not been set on the request.');
            }

            return $this->getAttribute('session');

        }

        public function expires(int $default = 0) :int
        {

            return (int) $this->query('expires', $default);

        }

        public function hasSession() : bool
        {

            $session = $this->getAttribute('session');

            return $session instanceof Session;

        }

        public function loadingScript() :string
        {

            return trim($this->getServerParams()['SCRIPT_NAME'] ?? '', DIRECTORY_SEPARATOR);

        }

        public function isWpAdmin() : bool
        {

            // A request to the admin dashboard. We can catch that within admin_init
            return Str::contains($this->loadingScript(), $this->getAttribute('_wp_admin_folder')) && ! $this->isWpAjax();


        }

        public function isWpAjax() : bool
        {

            return $this->loadingScript() === 'wp-admin/admin-ajax.php';

        }

        public function isWpFrontEnd() : bool
        {

            return $this->loadingScript() === 'index.php';

        }

        public function isApiEndPoint() : bool
        {

            $endpoints = $this->getAttribute('_api.endpoints', []);

            foreach ($endpoints as $endpoint) {

                if ( Str::startsWith(trim($this->path(), '/'), trim($endpoint, '/')) ) {

                    return true;

                }

            }

            return false;

        }



    }