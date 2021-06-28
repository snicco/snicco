<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriFactoryInterface;
    use Psr\Http\Message\UriInterface;
    use WPEmerge\Application\Application;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Url;
    use WPEmerge\View\ViewFactory;

    /**
     * @property Session $session
     * @property Application $app
     * @property HttpKernel $kernel
     * @property ServerRequestFactoryInterface $request_factory
     */
    trait MakesHttpRequests
    {

        /**
         * Additional headers for the request.
         *
         * @var array
         */
        protected $default_headers = [];

        /**
         * Additional cookies for the request.
         *
         * @var array
         */
        protected $default_cookies = [];

        /**
         * Additional server variables for the request.
         *
         * @var array
         */
        protected $default_server_variables = [];

        /**
         * Indicates whether redirects should be followed.
         *
         * @var bool
         */
        protected $follow_redirects = false;

        /**
         * Define additional headers to be sent with the request.
         *
         * @param  array  $headers
         * @return $this
         */
        public function withHeaders(array $headers)
        {
            $this->default_headers = array_merge($this->default_headers, $headers);

            return $this;
        }

        /**
         * Add a header to be sent with the request.
         *
         * @param  string  $name
         * @param  string  $value
         * @return $this
         */
        public function withHeader(string $name, string $value)
        {
            $this->default_headers[$name] = $value;

            return $this;
        }

        /**
         * Flush all the configured headers.
         *
         * @return $this
         */
        public function flushHeaders()
        {
            $this->default_headers = [];

            return $this;
        }

        /**
         * Define a set of server variables to be sent with the requests.
         *
         * @param  array  $server
         * @return $this
         */
        public function withServerVariables(array $server)
        {
            $this->default_server_variables = $server;

            return $this;
        }

        /**
         * Disable middleware for the test.
         *
         * @param  string|array|null  $middleware
         * @return $this
         */
        public function withoutMiddleware($middleware = null)
        {
            if (is_null($middleware)) {

                $this->app->config()->set('middleware.disabled', true );

                return $this;

            }

            foreach ((array) $middleware as $abstract) {

                $this->app->container()->instance($abstract, new class extends Middleware
                {
                    public function handle(Request $request, Delegate $next) :ResponseInterface
                    {
                        return $next($request);
                    }
                });

            }

            return $this;
        }

        /**
         * Define additional cookies to be sent with the request.
         *
         * @param  array  $cookies
         * @return $this
         */
        public function withCookies(array $cookies)
        {
            $this->default_cookies = array_merge($this->default_cookies, $cookies);

            return $this;
        }

        /**
         * Add a cookie to be sent with the request.
         *
         * @param  Cookie  $cookie
         *
         * @return $this
         */
        public function withCookie(string $name, string $value )
        {
            $this->default_cookies[$name] = $value;

            return $this;
        }

        /**
         * Automatically follow any redirects returned from the response.
         *
         * @return $this
         */
        public function followingRedirects()
        {
            $this->follow_redirects = true;

            return $this;
        }

        /**
         * Set the referer header and previous URL session value in order to simulate a previous request.
         *
         * @param  string  $url
         * @return $this
         */
        public function from(string $url)
        {
            if ( $this->session ) {

                $this->session->setPreviousUrl($url);

            }

            return $this->withHeader('referer', $url);
        }

        /**
         * Visit the given URI with a GET request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function get($uri, array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);

            $server = array_merge(['REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => 'index.php'], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('GET', $uri, $server);

            parse_str($uri->getQuery(), $query);
            $request = $request->withQueryParams($query);

            return $this->performRequest($request, $headers);

        }

        /**
         * Visit the given URI with a POST request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $data
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function post($uri, array $data = [], array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);

            $server = array_merge(['REQUEST_METHOD' => 'POST', 'SCRIPT_NAME' => 'index.php'], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('POST', $uri, $server);

            $request = $request->withParsedBody($data);

            return $this->performRequest($request, $headers);

        }


         /**
         * Visit the given URI with a PATCH request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $data
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function patch($uri, array $data = [], array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);

            $server = array_merge(['REQUEST_METHOD' => 'PATCH', 'SCRIPT_NAME' => 'index.php'], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('PATCH', $uri, $server);

            $request = $request->withParsedBody($data);

            return $this->performRequest($request, $headers);

        }

        /**
         * Visit the given URI with a POST request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $data
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function delete($uri, array $data = [], array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);

            $server = array_merge(['REQUEST_METHOD' => 'DELETE', 'SCRIPT_NAME' => 'index.php',], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('DELETE', $uri, $server);

            $request = $request->withParsedBody($data);

            return $this->performRequest($request, $headers);

        }

        private function addHeaders(ServerRequestInterface $request, array $headers) : ServerRequestInterface
        {

            foreach ($headers as $name => $value ) {
                $request = $request->withAddedHeader($request, $headers);
            }

            return $request;

        }

        private function performRequest(ServerRequestInterface $request, array $headers, string $type = 'web') : TestResponse
        {
            $request = $this->addHeaders($request, $headers);
            $request = new Request($this->addCookies($request));

            if ( $type === 'web') {
                $request = new IncomingWebRequest($request, 'wordpress-template.php');
            }
            elseif ( $type === 'admin') {
                $request = new IncomingAdminRequest($request);
            }
            elseif ( $type === 'ajax') {
                $request = new IncomingAjaxRequest($request);
            }

            if ( ! $this->set_up_has_run ) {
                $this->boot();
            }

            $this->loadRoutes();

            /** @var Response $response */
            $response = $this->kernel->run($request);

            $response = new TestResponse($response);

            $view_factory = $this->app->resolve(ViewFactory::class);

            if ( $view_factory->renderedView() instanceof ViewInterface ) {
                $response->setRenderedView($view_factory->renderedView());
            }

            if ( $this->session instanceof Session ) {
                $response->setSession($this->session);
            }

            return $response;

        }

        private function addCookies(ServerRequestInterface $request) : ServerRequestInterface
        {

            foreach ($this->default_cookies as $name => $value ) {

                $request = $request->withAddedHeader('Cookie', "$name=$value");

            }

            return $request;

        }

        private function createUri($uri) : UriInterface
        {

            if (is_string($uri) ) {
                $uri = Url::addLeading($uri);
            }

            $uri = $uri instanceof UriInterface
                ? $uri
                : $this->app->resolve(UriFactoryInterface::class)->createUri($uri);

            if ( ! $uri->getScheme() ) {
                $uri = $uri->withScheme('https');
            }

            if ( ! $uri->getHost() ) {

                $uri = $uri->withHost(parse_url(SITE_URL, PHP_URL_HOST));

            }

            return $uri;

        }

    }

