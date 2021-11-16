<?php

declare(strict_types=1);

namespace Snicco\Http\Psr7;

use WP_User;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Http\Cookies;
use Snicco\Routing\Route;
use Snicco\Session\Session;
use Snicco\Support\Repository;
use Snicco\Validation\Validator;
use Psr\Http\Message\UriInterface;
use Snicco\Traits\ValidatesWordpressNonces;
use Psr\Http\Message\ServerRequestInterface;

class Request implements ServerRequestInterface
{
    
    use ImplementsPsr7Request;
    use InspectsRequest;
    use InteractsWithInput;
    use ValidatesWordpressNonces;
    
    public function __construct(ServerRequestInterface $psr_request)
    {
        $this->psr_request = $psr_request;
    }
    
    /**
     * This method stores the URI that is used for matching against the routes
     * inside the AbstractRouteCollection. This URI is modified inside the CORE Middleware
     * for wp-admin and admin-ajax routes
     * to provide a more friendly api for matching these type of routes.
     * For admin routes the [page] query parameter is appended to the wp-admin url.
     * For ajax routes the [action] query parameter is appended to the admin-ajax url.
     * This is stored in an additional attribute to not tamper with the "real" requested URL.
     * This URI shall not be used anymore BESIDES FOR MATCHING A ROUTE.
     */
    public function withRoutingUri(UriInterface $uri) :Request
    {
        return $this->withAttribute('routing.uri', $uri);
    }
    
    public function withRoute(Route $route) :Request
    {
        return $this->withAttribute('_route', $route);
    }
    
    public function withCookies(array $cookies) :Request
    {
        return $this->withAttribute('cookies', new Repository($cookies));
    }
    
    public function withSession(Session $session_store) :Request
    {
        return $this->withAttribute('session', $session_store);
    }
    
    public function withUserId(int $user_id) :Request
    {
        return $this->withAttribute('_current_user_id', $user_id);
    }
    
    public function withValidator(Validator $v) :Request
    {
        return $this->withAttribute('_validator', $v);
    }
    
    public function validator() :Validator
    {
        $v = $this->getAttribute('_validator');
        
        if ( ! $v instanceof Validator) {
            throw new RuntimeException('A validator instance has not been set on the request.');
        }
        
        return $v;
    }
    
    /**
     * @todo Figure out how psr7 immutability will affect this.
     */
    public function user() :WP_User
    {
        $user = $this->getAttribute('_current_user');
        
        if ( ! $user instanceof WP_User) {
            $this->psr_request =
                $this->psr_request->withAttribute('_current_user', $user = WP::currentUser());
            
            return $user;
        }
        
        return $user;
    }
    
    public function userId() :int
    {
        return $this->getAttribute('_current_user_id', 0);
    }
    
    public function authenticated() :bool
    {
        return WP::isUserLoggedIn();
    }
    
    public function userAgent()
    {
        return substr($this->getHeaderLine('User-Agent'), 0, 500);
    }
    
    public function fullPath() :string
    {
        $fragment = $this->getUri()->getFragment();
        
        return ($fragment !== '')
            ? $this->getRequestTarget().'#'.$fragment
            : $this->getRequestTarget();
    }
    
    public function url() :string
    {
        return preg_replace('/\?.*/', '', $this->getUri());
    }
    
    public function fullUrl() :string
    {
        return $this->getUri()->__toString();
    }
    
    /**
     * @internal
     */
    public function routingPath() :string
    {
        $uri = $this->getAttribute('routing.uri');
        
        /** @var UriInterface $uri */
        $uri = $uri ?? $this->getUri();
        
        return $uri->getPath();
    }
    
    public function loadingScript() :string
    {
        return trim($this->getServerParams()['SCRIPT_NAME'] ?? '', DIRECTORY_SEPARATOR);
    }
    
    public function cookies() :Repository
    {
        /** @var Repository $bag */
        $bag = $this->getAttribute('cookies', new Repository());
        
        if ($bag->all() === []) {
            $cookies = Cookies::parseHeader($this->getHeader('Cookie'));
            
            $bag->add($cookies);
        }
        
        return $bag;
    }
    
    public function session() :Session
    {
        if ( ! $this->hasSession()) {
            throw new RuntimeException('A session has not been set on the request.');
        }
        
        return $this->getAttribute('session');
    }
    
    public function route() :?Route
    {
        return $this->getAttribute('_route');
    }
    
    public function hasSession() :bool
    {
        $session = $this->getAttribute('session');
        
        return $session instanceof Session;
    }
    
    public function expires(int $default = 0) :int
    {
        return (int) $this->query('expires', $default);
    }
    
    public function isWpAdmin() :bool
    {
        // A request to the admin dashboard. We can catch that within admin_init
        return Str::contains($this->loadingScript(), 'wp-admin') && ! $this->isWpAjax();
    }
    
    public function isWpAjax() :bool
    {
        return Str::contains($this->loadingScript(), 'wp-admin/admin-ajax.php');
    }
    
    public function isWpFrontEnd() :bool
    {
        return ! ($this->isWpAjax() || $this->isWpAdmin())
               && Str::contains($this->loadingScript(), 'index.php');
    }
    
    public function path() :string
    {
        return $this->getUri()->getPath();
    }
    
    public function decodedPath() :string
    {
        return rawurldecode($this->path());
    }
    
    public function routeIs(...$patterns) :bool
    {
        $route = $this->route();
        
        if ( ! $route instanceof Route) {
            return false;
        }
        
        if (is_null($name = $route->getName())) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $name)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function fullUrlIs(...$patterns) :bool
    {
        $url = $this->fullUrl();
        
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function pathIs(...$patterns) :bool
    {
        $path = Url::addLeading($this->decodedPath());
        
        foreach ($patterns as $pattern) {
            if (Str::is(Url::addLeading($pattern), $path)) {
                return true;
            }
        }
        
        return false;
    }
    
}