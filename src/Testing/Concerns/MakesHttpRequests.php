<?php


    declare(strict_types = 1);


    namespace BetterWP\Testing\Concerns;

    use BetterWP\Support\WP;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriFactoryInterface;
    use Psr\Http\Message\UriInterface;
    use Tests\helpers\CreatesWpUrls;
    use BetterWP\Application\Application;
    use BetterWP\Application\Config;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Contracts\ViewInterface;
    use BetterWP\Events\IncomingAdminRequest;
    use BetterWP\Events\IncomingAjaxRequest;
    use BetterWP\Events\IncomingWebRequest;
    use BetterWP\Http\Cookie;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\HttpKernel;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Session\Session;
    use BetterWP\Support\Str;
    use BetterWP\Support\Url;
    use BetterWP\Testing\TestResponse;
    use BetterWP\View\ViewFactory;

    /**
     * @property Session $session
     * @property Application $app
     * @property ServerRequestFactoryInterface $request_factory
     * @property bool $routes_loaded
     * @property Config $config
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
        private $follow_redirects = false;

        /**
         * Define additional headers to be sent with the request.
         *
         * @param  array  $headers
         *
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
         *
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
         *
         * @return $this
         */
        public function withServerVariables(array $server)
        {

            $this->default_server_variables = $server;

            return $this;
        }

        /**
         * Define additional cookies to be sent with the request.
         *
         * @param  array  $cookies
         *
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
        public function withCookie(string $name, string $value)
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
         * Set the referer header and previous URL session value in order to simulate a previous
         * request.
         *
         * @param  string  $url
         *
         * @return $this
         */
        public function from(string $url)
        {

            if ($this->session) {

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

            $server = array_merge([
                'REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('GET', $uri, $server);

            return $this->performRequest($request, $headers);

        }

        public function getJson($uri, array $headers = []) : TestResponse
        {

            $headers = array_merge($headers, ['Accept' => 'application/json']);

            return $this->get($uri, $headers);

        }

        /**
         * Visit the given ADMIN PAGE URI with a GET request.
         *
         * @param  string  $admin_page_slug  The [page] query parameter for the admin page. May
         *     contain additional query parameters.
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function getAdminPage(string $admin_page_slug, array $headers = []) : TestResponse
        {

            $uri = $this->adminPageUri($admin_page_slug);

            $server = array_merge([
                'REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => 'wp-admin/index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('GET', $uri, $server);

            parse_str($uri->getQuery(), $query);
            $request = $request->withQueryParams($query);

            return $this->performRequest($request, $headers);

        }

        public function options($uri, array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);

            $server = array_merge([
                'REQUEST_METHOD' => 'OPTIONS', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('OPTIONS', $uri, $server);

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

            $server = array_merge([
                'REQUEST_METHOD' => 'POST', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
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
            $server = array_merge([
                'REQUEST_METHOD' => 'PATCH', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('PATCH', $uri, $server);

            $request = $request->withParsedBody($data);

            return $this->performRequest($request, $headers);

        }

        /**
         * Visit the given URI with a PUT request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $data
         * @param  array  $headers
         *
         * @return TestResponse
         */
        public function put($uri, array $data = [], array $headers = []) : TestResponse
        {

            $uri = $this->createUri($uri);
            $server = array_merge([
                'REQUEST_METHOD' => 'PUT', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('PUT', $uri, $server);

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

            $server = array_merge([
                'REQUEST_METHOD' => 'DELETE', 'SCRIPT_NAME' => 'index.php',
            ], $this->default_server_variables);
            $request = $this->request_factory->createServerRequest('DELETE', $uri, $server);

            $request = $request->withParsedBody($data);

            return $this->performRequest($request, $headers);

        }

        protected function addHeaders(ServerRequestInterface $request, array $headers = []) : ServerRequestInterface
        {

            $headers = array_merge($headers, $this->default_headers);
            foreach ($headers as $name => $value) {
                $request = $request->withAddedHeader($name, $headers);
            }

            return $request;

        }

        protected function addCookies(ServerRequestInterface $request) : ServerRequestInterface
        {

            foreach ($this->default_cookies as $name => $value) {

                $request = $request->withAddedHeader('Cookie', "$name=$value");

            }

            return $request;

        }

        protected function toTestResponse(Response $response ) : TestResponse
        {

            $response = new TestResponse($response);

            $view_factory = $this->app->resolve(ViewFactory::class);

            if ($view_factory->renderedView() instanceof ViewInterface) {
                $response->setRenderedView($view_factory->renderedView());
            }

            if ($this->session instanceof Session) {
                $response->setSession($this->session);
            }

            $response->setApp($this->app);

            return $response;

        }

        private function performRequest(ServerRequestInterface $request, array $headers, string $type = 'web') : TestResponse
        {

            $request = $this->addHeaders($request, $headers);
            $request = new Request($this->addCookies($request));

            parse_str($request->getUri()->getQuery(), $query);
            $request = $request->withQueryParams($query);

            $this->withRequest($request);

            if ($type === 'web') {
                $request = new IncomingWebRequest($request, 'wordpress-template.php');
            }
            elseif ($type === 'admin') {
                $request = new IncomingAdminRequest($request);
            }
            elseif ($type === 'ajax') {
                $request = new IncomingAjaxRequest($request);
            }

            if ( ! $this->set_up_has_run) {
                $this->boot();
            }
            else {
                $this->bindRequest();
            }

            if ( ! $this->routes_loaded) {

                $this->loadRoutes();

            }

            /** @var Response $response */
            $response = $this->app->resolve(HttpKernel::class)->run($request);

            if ($this->follow_redirects) {

                return $this->followRedirects($response);

            }

            return $this->toTestResponse($response);

        }

        private function createUri($uri) : UriInterface
        {

            if (is_string($uri) && ! Str::contains($uri, 'http')) {
                $uri = Url::addLeading($uri);
            }

            $uri = $uri instanceof UriInterface
                ? $uri
                : $this->app->resolve(UriFactoryInterface::class)->createUri($uri);

            if ( ! $uri->getScheme()) {
                $uri = $uri->withScheme('https');
            }

            if ( ! $uri->getHost() ) {

                $uri = $uri->withHost(parse_url($this->config->get('app.url') ?? WP::siteUrl(), PHP_URL_HOST));

            }

            return $uri;

        }

        private function adminPageUri(string $admin_page_slug) : UriInterface
        {

            $query = urlencode($admin_page_slug);
            $uri = Url::combineAbsPath($this->config->get('app.url'), "wp-admin/admin.php?page=$query");

            return $this->app->resolve(UriFactoryInterface::class)->createUri($uri);

        }

        private function followRedirects(Response $response)
        {

            $this->follow_redirects = false;

            while ($response->isRedirect()) {
                $response = $this->get($response->getHeaderLine('Location'));
            }

            return $response;

        }

    }

