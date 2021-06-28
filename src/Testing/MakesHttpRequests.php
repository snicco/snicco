<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriInterface;
    use WPEmerge\Application\Application;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Session\Session;

    /**
     * @property Session $session
     * @property Application $app
     * @property ServerRequestFactoryInterface $request_factory
     * @property HttpKernel $kernel
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
            $this->session->setPreviousUrl($url);

            return $this->withHeader('referer', $url);
        }

        /**
         * Visit the given URI with a GET request.
         *
         * @param  string|UriInterface  $uri
         * @param  array  $_GET
         * @param  array  $headers
         * @param  array  $serer_params
         *
         * @return TestResponse
         */
        public function get($uri, array $_GET = [], array $headers = [], array $serer_params = []) : TestResponse
        {

            $serer_params = array_merge($serer_params, $this->default_server_variables, ['REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => 'index.php']);
            $request = $this->request_factory->createServerRequest('GET', $uri, $serer_params);

            $request = $this->addHeaders($request, $headers);
            $request = $request->withQueryParams($_GET);

            return $this->performRequest($request);

        }

        /**
         * Visit the given URI with a GET request sending/expecting JSON
         *
         * @param  string|UriInterface  $uri
         * @param  array  $serer_params
         * @return TestResponse
         */
        public function getJson($uri, array $_GET = [], array $headers = [], array $serer_params = []) {

            return $this->json('GET', $uri, $_GET, [], $headers, $serer_params);

        }

        /**
         * Visit the given URI with a GET request sending/expecting JSON
         *
         * @param  string  $method
         * @param  string|UriInterface  $uri
         * @param  array  $_get
         * @param  array  $_post
         * @param  array  $headers
         * @param  array  $serer_params
         *
         * @return TestResponse
         */
        private function json(string $method, $uri, array $_get = [], array $_post = [], array $headers = [], array $serer_params = [])
        {

            $serer_params = array_merge($serer_params, $this->default_server_variables, ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => 'index.php']);
            $request = $this->request_factory->createServerRequest($method, $uri, $serer_params);

            $request = $this->addHeaders($request, $headers);

            if ( count($_get)) {
                $request = $request->withQueryParams($_get);

            } elseif (count($_post)) {
                $request = $request->withParsedBody(json_encode($_post));
            }



            $content = json_encode($data);

            $headers = array_merge([
                'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
                'CONTENT_TYPE' => 'application/json',
                'Accept' => 'application/json',
            ], $headers);

            return $this->call(
                $method,
                $uri,
                [],
                $this->prepareCookiesForJsonRequest(),
                $files,
                $this->transformHeadersToServerVars($headers),
                $content
            );
        }


        private function performRequest( ServerRequestInterface $request, string $type = 'web' ) : TestResponse
        {

            $request = new Request($request);

            foreach ( $this->default_headers as $header => $value ) {

                $request = $request->withAddedHeader($header, $value);

            }

            foreach ($this->default_cookies as $name => $value ) {

                $request->withAddedHeader('Cookie', "$name=$value");

            }

            if ($type === 'admin') {
                $request = new IncomingAdminRequest($request);
            } elseif ($type === 'ajax') {
                $request = new IncomingAjaxRequest($request);
            } elseif ($type === 'web') {
                $request = new IncomingWebRequest($request, 'wordpress-template.php');
            }

            /** @var Response $response */
            $response = $this->kernel->run($request);

            return new TestResponse($response);

        }

        /**
         * Visit the given URI with a GET request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function getJson($uri, array $headers = [])
        {
            return $this->json('GET', $uri, [], $headers);
        }

        /**
         * Visit the given URI with a POST request.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function post($uri, array $data = [], array $headers = [])
        {
            $server = $this->transformHeadersToServerVars($headers);
            $cookies = $this->prepareCookiesForRequest();

            return $this->call('POST', $uri, $data, $cookies, [], $server);
        }

        /**
         * Visit the given URI with a POST request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function postJson($uri, array $data = [], array $headers = [])
        {
            return $this->json('POST', $uri, $data, $headers);
        }

        /**
         * Visit the given URI with a PUT request.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function put($uri, array $data = [], array $headers = [])
        {
            $server = $this->transformHeadersToServerVars($headers);
            $cookies = $this->prepareCookiesForRequest();

            return $this->call('PUT', $uri, $data, $cookies, [], $server);
        }

        /**
         * Visit the given URI with a PUT request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function putJson($uri, array $data = [], array $headers = [])
        {
            return $this->json('PUT', $uri, $data, $headers);
        }

        /**
         * Visit the given URI with a PATCH request.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function patch($uri, array $data = [], array $headers = [])
        {
            $server = $this->transformHeadersToServerVars($headers);
            $cookies = $this->prepareCookiesForRequest();

            return $this->call('PATCH', $uri, $data, $cookies, [], $server);
        }

        /**
         * Visit the given URI with a PATCH request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function patchJson($uri, array $data = [], array $headers = [])
        {
            return $this->json('PATCH', $uri, $data, $headers);
        }

        /**
         * Visit the given URI with a DELETE request.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function delete($uri, array $data = [], array $headers = [])
        {
            $server = $this->transformHeadersToServerVars($headers);
            $cookies = $this->prepareCookiesForRequest();

            return $this->call('DELETE', $uri, $data, $cookies, [], $server);
        }

        /**
         * Visit the given URI with a DELETE request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function deleteJson($uri, array $data = [], array $headers = [])
        {
            return $this->json('DELETE', $uri, $data, $headers);
        }

        /**
         * Visit the given URI with an OPTIONS request.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function options($uri, array $data = [], array $headers = [])
        {
            $server = $this->transformHeadersToServerVars($headers);
            $cookies = $this->prepareCookiesForRequest();

            return $this->call('OPTIONS', $uri, $data, $cookies, [], $server);
        }

        /**
         * Visit the given URI with an OPTIONS request, expecting a JSON response.
         *
         * @param  string  $uri
         * @param  array  $data
         * @param  array  $headers
         * @return \Illuminate\Testing\TestResponse
         */
        public function optionsJson($uri, array $data = [], array $headers = [])
        {
            return $this->json('OPTIONS', $uri, $data, $headers);
        }



        /**
         * Call the given URI and return the Response.
         *
         * @param  string  $method
         * @param  string  $uri
         * @param  array  $parameters
         * @param  array  $cookies
         * @param  array  $files
         * @param  array  $server
         * @param  string|null  $content
         * @return \Illuminate\Testing\TestResponse
         */
        public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
        {
            $kernel = $this->app->make(HttpKernel::class);

            $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

            $symfonyRequest = SymfonyRequest::create(
                $this->prepareUrlForRequest($uri), $method, $parameters,
                $cookies, $files, array_replace($this->serverVariables, $server), $content
            );

            $response = $kernel->handle(
                $request = Request::createFromBase($symfonyRequest)
            );

            $kernel->terminate($request, $response);

            if ($this->followRedirects) {
                $response = $this->followRedirects($response);
            }

            return $this->createTestResponse($response);
        }

        /**
         * Turn the given URI into a fully qualified URL.
         *
         * @param  string  $uri
         * @return string
         */
        protected function prepareUrlForRequest($uri)
        {
            if (Str::startsWith($uri, '/')) {
                $uri = substr($uri, 1);
            }

            return trim(url($uri), '/');
        }

        /**
         * Transform headers array to array of $_SERVER vars with HTTP_* format.
         *
         * @param  array  $headers
         * @return array
         */
        protected function transformHeadersToServerVars(array $headers)
        {
            return collect(array_merge($this->defaultHeaders, $headers))->mapWithKeys(function ($value, $name) {
                $name = strtr(strtoupper($name), '-', '_');

                return [$this->formatServerHeaderKey($name) => $value];
            })->all();
        }

        /**
         * Format the header name for the server array.
         *
         * @param  string  $name
         * @return string
         */
        protected function formatServerHeaderKey($name)
        {
            if (! Str::startsWith($name, 'HTTP_') && $name !== 'CONTENT_TYPE' && $name !== 'REMOTE_ADDR') {
                return 'HTTP_'.$name;
            }

            return $name;
        }

        /**
         * Extract the file uploads from the given data array.
         *
         * @param  array  $data
         * @return array
         */
        protected function extractFilesFromDataArray(&$data)
        {
            $files = [];

            foreach ($data as $key => $value) {
                if ($value instanceof SymfonyUploadedFile) {
                    $files[$key] = $value;

                    unset($data[$key]);
                }

                if (is_array($value)) {
                    $files[$key] = $this->extractFilesFromDataArray($value);

                    $data[$key] = $value;
                }
            }

            return $files;
        }

        /**
         * If enabled, encrypt cookie values for request.
         *
         * @return array
         */
        protected function prepareCookiesForRequest()
        {
            if (! $this->encryptCookies) {
                return array_merge($this->defaultCookies, $this->unencryptedCookies);
            }

            return collect($this->defaultCookies)->map(function ($value, $key) {
                return encrypt(CookieValuePrefix::create($key, app('encrypter')->getKey()).$value, false);
            })->merge($this->unencryptedCookies)->all();
        }

        /**
         * If enabled, add cookies for JSON requests.
         *
         * @return array
         */
        protected function prepareCookiesForJsonRequest()
        {
            return $this->withCredentials ? $this->prepareCookiesForRequest() : [];
        }

        /**
         * Follow a redirect chain until a non-redirect is received.
         *
         * @param  \Illuminate\Http\Response  $response
         * @return \Illuminate\Http\Response|\Illuminate\Testing\TestResponse
         */
        protected function followRedirects($response)
        {
            $this->followRedirects = false;

            while ($response->isRedirect()) {
                $response = $this->get($response->headers->get('Location'));
            }

            return $response;
        }

        /**
         * Create the test response instance from the given response.
         *
         * @param  \Illuminate\Http\Response  $response
         * @return \Illuminate\Testing\TestResponse
         */
        protected function createTestResponse($response)
        {
            return TestResponse::fromBaseResponse($response);
        }

        private function addHeaders(ServerRequestInterface $request, array $headers) : ServerRequestInterface
        {

            foreach ($headers as $name => $value ) {
                $request = $request->withAddedHeader($request, $headers);
            }

            return $request;

        }

    }

