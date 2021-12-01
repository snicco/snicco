<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Support\WP;
use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Support\Arr;
use Snicco\Http\HttpKernel;
use Snicco\Session\Session;
use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use InvalidArgumentException;
use Snicco\Application\Config;
use Snicco\Http\Psr7\Response;
use Snicco\Testing\TestResponse;
use Psr\Http\Message\UriInterface;
use Snicco\Application\Application;
use Snicco\View\Contracts\ViewInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * @property Session|null $session
 * @property Application $app
 * @property ServerRequestFactoryInterface $request_factory
 * @property bool $routes_loaded
 * @property Config $config
 */
trait MakesHttpRequests
{
    
    use BuildsWordPressUrls;
    
    protected array $default_headers = [];
    
    protected array $default_cookies = [];
    
    protected array $default_server_variables = [];
    
    protected array $default_attributes = [];
    
    private bool $with_trailing_slash = false;
    
    private bool $without_trailing_slash = false;
    
    private bool $follow_redirects = false;
    
    public function withHeaders(array $headers) :self
    {
        $this->default_headers = array_merge($this->default_headers, $headers);
        
        return $this;
    }
    
    public function flushHeaders() :self
    {
        $this->default_headers = [];
        
        return $this;
    }
    
    public function withServerVariables(array $server) :self
    {
        $this->default_server_variables = $server;
        
        return $this;
    }
    
    /**
     * @param  array<string, string>  $cookies
     */
    public function withCookies(array $cookies) :self
    {
        $this->default_cookies = array_merge($this->default_cookies, $cookies);
        
        return $this;
    }
    
    public function withCookie(string $name, string $value) :self
    {
        $this->default_cookies[$name] = $value;
        
        return $this;
    }
    
    public function followingRedirects() :self
    {
        $this->follow_redirects = true;
        return $this;
    }
    
    public function fromLocalhost() :self
    {
        return $this->fromIpAddress('127.0.0.1');
    }
    
    public function fromIpAddress(string $ip) :self
    {
        $this->default_attributes['ip_address'] = $ip;
        $this->default_server_variables['REMOTE_ADDR'] = $ip;
        return $this;
    }
    
    /**
     * Add trailing slashes to all urls.
     */
    public function withTrailingSlash() :self
    {
        $this->with_trailing_slash = true;
        
        return $this;
    }
    
    /**
     * Remove trailing slashes to all urls.
     */
    public function removeTrailingSlash() :self
    {
        $this->without_trailing_slash = true;
        
        return $this;
    }
    
    /**
     * Set the referer header and previous URL session value in order to simulate a previous
     * request.
     */
    public function from(string $url) :self
    {
        if (isset($this->session)) {
            $this->session->setPreviousUrl($url);
        }
        
        return $this->withHeader('referer', $url);
    }
    
    public function withHeader(string $name, string $value) :self
    {
        $this->default_headers[$name] = $value;
        
        return $this;
    }
    
    /**
     * Visit the given URI with a GET request accepting json.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function getJson($uri, array $headers = []) :TestResponse
    {
        $headers = array_merge($headers, ['Accept' => 'application/json']);
        
        return $this->get($uri, $headers);
    }
    
    /**
     * Visit the given URI with a GET request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function get($uri, array $headers = []) :TestResponse
    {
        return $this->performRequest($this->frontendRequest('GET', $uri), $headers);
    }
    
    public function frontendRequest(string $method = 'GET', $uri = '/') :Request
    {
        $method = strtoupper($method);
        $uri = $this->createUri($uri);
        
        $this->default_server_variables['REQUEST_METHOD'] ??= $method;
        $this->default_server_variables['SCRIPT_NAME'] ??= 'index.php';
        
        $request = new Request(
            $this->request_factory->createServerRequest(
                $method,
                $uri,
                $this->default_server_variables
            )
        );
        
        parse_str($request->getUri()->getQuery(), $query);
        
        return $request->withQueryParams($query);
    }
    
    /**
     * Visit the given ADMIN PAGE URI with a GET request.
     *
     * @param  string  $admin_page_slug  The [page] query parameter for the admin page.
     * May contain additional query parameters.
     * @param  array<string, string>  $headers
     * @param  string  $parent
     *
     * @return TestResponse
     */
    public function getAdminPage(string $admin_page_slug, array $headers = [], string $parent = 'admin.php') :TestResponse
    {
        $request = $this->adminRequest('GET', $admin_page_slug, $parent);
        
        return $this->performRequest($request, $headers);
    }
    
    public function adminRequest(string $method, $menu_slug, $parent = 'admin.php') :Request
    {
        $method = strtoupper($method);
        $url = $this->adminUrlTo($menu_slug, $parent);
        $uri = $this->createUri($url);
        
        $this->default_server_variables['REQUEST_METHOD'] ??= $method;
        $this->default_server_variables['SCRIPT_NAME'] ??= $this->adminScriptName($parent);
        
        $request = new Request(
            $this->request_factory->createServerRequest(
                $method,
                $uri,
                $this->default_server_variables
            )
        );
        
        $this->gotToPluginPage($menu_slug, $parent);
        
        return $request->withQueryParams(['page' => $menu_slug]);
    }
    
    public function getAdminAjax(array $query, array $headers = []) :TestResponse
    {
        if ( ! isset($query['action'])) {
            throw new InvalidArgumentException('Action parameters is missing');
        }
        
        $request = $this->adminAjaxRequest('GET', $query['action'], Arr::except($query, 'action'));
        
        return $this->performRequest($request, $headers);
    }
    
    public function adminAjaxRequest(string $method, string $action, array $data = []) :Request
    {
        $method = strtoupper($method);
        $uri = $this->createUri($this->ajaxUrl($action));
        
        $this->default_server_variables['REQUEST_METHOD'] ??= $method;
        $this->default_server_variables['SCRIPT_NAME'] ??= $this->ajaxScriptName();
        
        $request = new Request(
            $this->request_factory->createServerRequest(
                $method,
                $uri,
                $this->default_server_variables
            )
        );
        
        if ($request->isGet()) {
            return $request->withQueryParams(array_merge(['action' => $action], $data));
        }
        
        return $request->withParsedBody(array_merge(['action' => $action], $data));
    }
    
    /**
     * Visit the given URI with a OPTIONS request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function options($uri, array $headers = []) :TestResponse
    {
        $request = $this->frontendRequest('OPTIONS', $uri);
        
        return $this->performRequest($request, $headers);
    }
    
    /**
     * Visit the given URI with a POST request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, mixed>  $parsed_data
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function post($uri, array $parsed_data = [], array $headers = []) :TestResponse
    {
        $request = $this->frontendRequest('POST', $uri)->withParsedBody($parsed_data);
        
        return $this->performRequest($request, $headers);
    }
    
    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, mixed>  $parsed_data
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function patch($uri, array $parsed_data = [], array $headers = []) :TestResponse
    {
        $request = $this->frontendRequest('PATCH', $uri)->withParsedBody($parsed_data);
        
        return $this->performRequest($request, $headers);
    }
    
    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, mixed>  $parsed_data
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function put($uri, array $parsed_data = [], array $headers = []) :TestResponse
    {
        $request = $this->frontendRequest('PUT', $uri)->withParsedBody($parsed_data);
        
        return $this->performRequest($request, $headers);
    }
    
    /**
     * Visit the given URI with a POST request.
     *
     * @param  string|UriInterface  $uri
     * @param  array<string, mixed>  $parsed_data
     * @param  array<string, string>  $headers
     *
     * @return TestResponse
     */
    public function delete($uri, array $parsed_data = [], array $headers = []) :TestResponse
    {
        $request = $this->frontendRequest('DELETE', $uri)->withParsedBody($parsed_data);
        
        return $this->performRequest($request, $headers);
    }
    
    protected function addHeaders(Request $request, array $headers = []) :Request
    {
        $headers = array_merge($headers, $this->default_headers);
        foreach ($headers as $name => $value) {
            $request = $request->withAddedHeader($name, $headers);
        }
        
        return $request;
    }
    
    protected function addCookies(Request $request) :Request
    {
        foreach ($this->default_cookies as $name => $value) {
            $request = $request->withAddedHeader('Cookie', "$name=$value");
        }
        
        return $request;
    }
    
    protected function addAttributes(Request $request) :Request
    {
        foreach ($this->default_attributes as $attribute => $value) {
            $request = $request->withAttribute($attribute, $value);
        }
        
        return $request;
    }
    
    protected function toTestResponse(Response $response) :TestResponse
    {
        $response = new TestResponse($response);
        
        /** @var ViewEngine $view_factory */
        $view_factory = $this->app->resolve(ViewEngine::class);
        
        if ($view_factory->rootView() instanceof ViewInterface) {
            $response->setRenderedView($view_factory->rootView());
        }
        
        if ($this->session instanceof Session) {
            $response->setSession($this->session);
        }
        
        $response->setApp($this->app);
        
        return $response;
    }
    
    private function gotToPluginPage($menu_slug, $parent)
    {
        global $pagenow, $plugin_page, $admin_page_hooks, $_parent_pages;
        $pagenow = $parent;
        $plugin_page = $menu_slug;
        $admin_page_hooks[$menu_slug] = Str::studly($menu_slug);
        add_action("toplevel_page_$menu_slug", function () { });
    }
    
    private function createUri($uri) :UriInterface
    {
        if (is_string($uri)) {
            if ( ! Str::contains($uri, 'http')) {
                $uri = Url::addLeading($uri);
            }
            
            if ($this->with_trailing_slash && ! Str::contains($uri, ['.php', '?'])) {
                $uri = Url::addTrailing($uri);
            }
            
            if ($this->without_trailing_slash) {
                $uri = Url::removeTrailing($uri);
            }
        }
        
        $uri = $uri instanceof UriInterface
            ? $uri
            : $this->app->resolve(UriFactoryInterface::class)->createUri($uri);
        
        if ( ! $uri->getScheme()) {
            $uri = $uri->withScheme('https');
        }
        
        if ( ! $uri->getHost()) {
            $uri = $uri->withHost(
                parse_url(
                    $this->config->get('app.url') ?? WP::siteUrl(),
                    PHP_URL_HOST
                )
            );
        }
        
        return $uri;
    }
    
    private function performRequest(Request $request, array $headers) :TestResponse
    {
        $this->request = $this->addRequestDefaults($request, $headers);
        
        unset($this->default_server_variables['SCRIPT_NAME']);
        
        if ( ! $this->app->hasBeenBootstrapped()) {
            $this->bootApp();
        }
        
        /** @var Response $response */
        $response = $this->app->resolve(HttpKernel::class)->run($this->request);
        
        if ($this->follow_redirects) {
            return $this->followRedirects($response);
        }
        
        return $this->toTestResponse($response);
    }
    
    private function followRedirects(Response $response)
    {
        $this->follow_redirects = false;
        
        if ( ! $response->isRedirect()) {
            return $this->toTestResponse($response);
        }
        
        while ($response->isRedirect()) {
            $location = $response->getHeaderLine('Location');
            $parts = parse_url($location);
            
            if (Str::contains($parts['path'], WP::wpAdminFolder())) {
                $this->default_server_variables['SCRIPT_NAME'] = $this->adminScriptName();
                $response = $this->get($location);
            }
            
            $response = $this->get($response->getHeaderLine('Location'));
        }
        
        return $response;
    }
    
    private function addRequestDefaults(Request $request, array $headers = []) :Request
    {
        $request = $this->addCookies($request);
        $request = $this->addHeaders($request, $headers);
        $request = $this->addAttributes($request);
        $this->instance(Request::class, $request);
        return $request;
    }
    
    private function adminScriptName($parent = 'admin.php') :string
    {
        return WP::wpAdminFolder()
               .DIRECTORY_SEPARATOR
               .$parent;
    }
    
    private function ajaxScriptName() :string
    {
        return $this->adminScriptName('admin-ajax.php');
    }
    
}



