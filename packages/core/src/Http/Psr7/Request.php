<?php

declare(strict_types=1);

namespace Snicco\Core\Http\Psr7;

use WP_User;
use Snicco\Support\Str;
use Snicco\Core\Support\WP;
use Snicco\Core\Support\Url;
use Snicco\Core\Http\Cookies;
use Snicco\Support\Repository;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Core\Routing\UrlMatcher\RoutingResult;

/**
 * @todo add ip() method
 */
class Request implements ServerRequestInterface
{
    
    use ImplementsPsr7Request;
    use InspectsRequest;
    use InteractsWithInput;
    
    public function __construct(ServerRequestInterface $psr_request)
    {
        $this->psr_request = $psr_request;
    }
    
    public function withRoutingResult(RoutingResult $route) :Request
    {
        return $this->withAttribute('_routing_result', $route);
    }
    
    public function withCookies(array $cookies) :Request
    {
        return $this->withAttribute('cookies', new Repository($cookies));
    }
    
    public function withUserId(int $user_id) :Request
    {
        return $this->withAttribute('_current_user_id', $user_id);
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
    
    // path + query + fragment
    public function fullRequestTarget() :string
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
    
    // host + path + query + fragment
    public function fullUrl() :string
    {
        return $this->getUri()->__toString();
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
    
    public function expires(int $default = 0) :int
    {
        return (int) $this->query('expires', $default);
    }
    
    public function path() :string
    {
        return $this->getUri()->getPath();
    }
    
    public function decodedPath() :string
    {
        $path = $this->path();
        return implode(
            '/',
            array_map(function ($part) {
                return rawurldecode(strtr($part, ['%2F' => '%252F']));
            }, explode('/', $path))
        );
    }
    
    public function routeIs(...$patterns) :bool
    {
        $route = $this->routingResult()->route();
        
        if ( ! $route) {
            return false;
        }
        
        $name = $route->getName();
        
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
        /** @var @todo Decoded or real path? */
        $path = Url::addLeading($this->decodedPath());
        
        foreach ($patterns as $pattern) {
            if (Str::is(Url::addLeading($pattern), $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function routingResult() :RoutingResult
    {
        return $this->getAttribute('_routing_result', RoutingResult::noMatch());
    }
    
}