<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\WP;
use Snicco\Support\Url;
use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Traits\InteractsWithTime;
use Snicco\Contracts\RouteUrlGenerator;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class UrlGenerator
{
    
    use InteractsWithTime;
    
    private bool              $trailing_slash;
    private RouteUrlGenerator $route_url;
    private MagicLink         $magic_link;
    
    /**
     * @var callable
     */
    private $request_resolver;
    
    public function __construct(RouteUrlGenerator $route_url, MagicLink $magic_link, bool $trailing_slash = false)
    {
        $this->route_url = $route_url;
        $this->magic_link = $magic_link;
        $this->trailing_slash = $trailing_slash;
    }
    
    public function setRequestResolver(callable $request_resolver)
    {
        $this->request_resolver = $request_resolver;
    }
    
    public function signedLogout(?int $user_id = null, string $redirect_on_logout = '/', int $expiration = 3600, bool $absolute = true) :string
    {
        $args = [
            'user_id' => $user_id ?? WP::userId(),
            'query' => [
                'redirect_to' => $redirect_on_logout,
            ],
        ];
        
        return $this->signedRoute('auth.logout', $args, $expiration, $absolute);
    }
    
    public function signedRoute(string $route, array $arguments = [], $expiration = 300, bool $absolute = false) :string
    {
        $query = Arr::pull($arguments, 'query', []);
        
        // signed() needs a path, so don't use absolute urls here.
        $route_path = $this->toRoute($route, $arguments, true, false);
        
        return $this->signed($route_path, $expiration, $absolute, $query);
    }
    
    /**
     * @throws ConfigurationException
     */
    public function toRoute(string $name, array $arguments = [], bool $secure = true, bool $absolute = false) :string
    {
        $query = Arr::pull($arguments, 'query', []);
        
        $path = $this->route_url->to($name, $arguments);
        
        return $this->to($path, $query, $secure, $absolute);
    }
    
    public function to($path, array $query = [], $secure = true, bool $absolute = false) :string
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
    
    public function getRequest() :Request
    {
        return call_user_func($this->request_resolver);
    }
    
    public function signed(string $path, $expiration = 300, $absolute = false, $query = []) :string
    {
        if (Url::isValidAbsolute($path)) {
            throw new ConfigurationException('Signed urls do not work with absolute urls.');
        }
        
        $expires = $this->availableAt($expiration);
        
        $query = array_merge(['expires' => $expires], $query);
        
        $url_with_expired_query_string = $this->to($path, $query, true, $absolute);
        
        $signature = $this->magic_link->create(
            $url_with_expired_query_string,
            $expires,
            $this->getRequest()
        );
        
        return $this->to(
            $path,
            array_merge($query, [MagicLink::QUERY_STRING_ID => $signature]),
            true,
            $absolute
        );
    }
    
    public function secure(string $path, array $query = []) :string
    {
        return $this->to($path, $query, true, true);
    }
    
    public function back(string $fallback = '') :string
    {
        $referrer = $this->getRequest()->getHeaderLine('referer');
        
        if ($referrer !== '') {
            return $this->to($referrer, [], true, Url::isValidAbsolute($referrer));
        }
        elseif ($fallback !== '') {
            return $this->to($fallback);
        }
        
        return $this->to('/');
    }
    
    public function current() :string
    {
        return $this->getRequest()->fullPath();
    }
    
    public function toLogin(string $redirect_on_login = '', bool $reauth = false) :string
    {
        return $this->to(WP::loginUrl($redirect_on_login, $reauth));
    }
    
    private function formatAbsolute(string $url, $absolute) :string
    {
        $host = parse_url($url)['host'];
        
        if ( ! $absolute) {
            return '/'.ltrim(Str::after($url, $host), '/');
        }
        
        return $url;
    }
    
    private function formatTrailing(string $path) :string
    {
        $parts = parse_url($path);
        
        $parts['path'] =
            $this->trailing_slash ? rtrim($parts['path'] ?? '/', '/').'/' : $parts['path'] ?? '/';
        
        if (strpos($parts['path'], '.php')) {
            $parts['path'] = rtrim($parts['path'], '/');
        }
        
        return Url::unParseUrl($parts);
    }
    
    private function formatRoot(string $scheme, string $root = null) :string
    {
        if (is_null($root)) {
            $request = $this->getRequest();
            
            $uri = $request->getUri();
            
            $root = $uri->getScheme().'://'.$uri->getHost();
        }
        
        $start = Str::startsWith($root, 'http://') ? 'http://' : 'https://';
        
        return rtrim(preg_replace('~'.$start.'~', $scheme, $root, 1), '/');
    }
    
    private function formatScheme($secure) :string
    {
        return $secure ? 'https://' : 'http://';
    }
    
    private function format(string $root, string $path) :string
    {
        $parts = explode('#', $path);
        
        [$path, $fragment] = [$parts[0], isset($parts[1]) ? '#'.$parts[1] : ''];
        
        $path = trim($path, '/');
        $root = rtrim($root, '/');
        
        $path = implode('/', array_map('rawurlencode', explode('/', $path)));
        
        return $root.'/'.$path.$fragment;
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
    
    private function addQueryString(string $uri, array $query) :string
    {
        $query = $this->buildQueryString($query);
        
        $uri .= $query === '' ? '' : '?'.$query;
        
        return $uri;
    }
    
    private function buildQueryString(array $query) :string
    {
        return trim(Arr::query($query), '&');
    }
    
}