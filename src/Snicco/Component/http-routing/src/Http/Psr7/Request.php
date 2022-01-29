<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use Snicco\Component\StrArr\Str;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\HttpRouting\Http\Cookies;
use Snicco\Component\ParameterBag\ParameterPag;
use Snicco\Component\HttpRouting\Http\Exceptions\RequestHasNoType;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;

final class Request implements ServerRequestInterface
{
    
    use ImplementsPsr7Request;
    use InspectsRequest;
    use InteractsWithInput;
    
    /**
     * @var @internal
     */
    const TYPE_ATTRIBUTE = '_request_type';
    
    /**
     * @var @internal
     */
    const TYPE_FRONTEND = 1;
    
    /**
     * @var @internal
     */
    const TYPE_ADMIN_AREA = 2;
    
    /**
     * @var @internal
     */
    const TYPE_API = 3;
    
    public function __construct(ServerRequestInterface $psr_request)
    {
        $this->psr_request = $psr_request;
    }
    
    public static function fromPsr(ServerRequestInterface $psr_request) :Request
    {
        return new self($psr_request);
    }
    
    final public function withRoutingResult(RoutingResult $route) :Request
    {
        return $this->withAttribute('_routing_result', $route);
    }
    
    final public function withCookies(array $cookies) :Request
    {
        return $this->withAttribute('cookies', new ParameterPag($cookies));
    }
    
    final public function userAgent()
    {
        return substr($this->getHeaderLine('User-Agent'), 0, 500);
    }
    
    // path + query + fragment
    final public function fullRequestTarget() :string
    {
        $fragment = $this->getUri()->getFragment();
        
        return ($fragment !== '')
            ? $this->getRequestTarget().'#'.$fragment
            : $this->getRequestTarget();
    }
    
    // scheme + host + path
    final public function url() :string
    {
        return preg_replace('/\?.*/', '', $this->getUri());
    }
    
    // scheme + host + path + query + fragment
    final public function fullUrl() :string
    {
        return $this->getUri()->__toString();
    }
    
    final public function cookies() :ParameterPag
    {
        /** @var ParameterPag $bag */
        $bag = $this->getAttribute('cookies', new ParameterPag());
        
        if ($bag->toArray() === []) {
            $cookies = Cookies::parseHeader($this->getHeader('Cookie'));
            
            $bag->add($cookies);
        }
        
        return $bag;
    }
    
    final function path() :string
    {
        return $this->getUri()->getPath();
    }
    
    final function decodedPath() :string
    {
        $path = $this->path();
        return implode(
            '/',
            array_map(function ($part) {
                return rawurldecode(strtr($part, ['%2F' => '%252F']));
            }, explode('/', $path))
        );
    }
    
    final function routeIs(string $pattern) :bool
    {
        $route = $this->routingResult()->route();
        
        if ( ! $route) {
            return false;
        }
        
        return Str::is($pattern, $route->getName());
    }
    
    /**
     * @note The full url is not urldecoded here.
     */
    final function fullUrlIs(...$patterns) :bool
    {
        $url = $this->fullUrl();
        
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    final function pathIs(...$patterns) :bool
    {
        $path = $this->decodedPath();
        
        foreach ($patterns as $pattern) {
            if (Str::is('/'.ltrim($pattern, '/'), $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    final function routingResult() :RoutingResult
    {
        return $this->getAttribute('_routing_result', RoutingResult::noMatch());
    }
    
    /**
     * A request is considered secure when the scheme is set to "https".
     * If your site runs behind a reverse proxy you have to make sure that your reverse proxy is
     * configured correctly for setting the HTTP_X_FORWARDED_PROTO header. It's purposely not
     * possible to configure trusted proxies because if this is not done configured at the server
     * level the entire WP application will misbehave anyway.
     *
     * @see ServerRequestCreator::createUriFromArray()
     */
    final public function isSecure() :bool
    {
        return 'https' === $this->getUri()->getScheme();
    }
    
    /**
     * @throws RequestHasNoType
     */
    final public function isToFrontend() :bool
    {
        return self::TYPE_FRONTEND === $this->getType();
    }
    
    /**
     * @throws RequestHasNoType
     */
    final public function isToAdminArea() :bool
    {
        return self::TYPE_ADMIN_AREA === $this->getType();
    }
    
    /**
     * @throws RequestHasNoType
     */
    final public function isToApiEndpoint() :bool
    {
        return self::TYPE_API === $this->getType();
    }
    
    final public function ip() :?string
    {
        return $this->server('REMOTE_ADDR');
    }
    
    /**
     * @throws RequestHasNoType
     */
    private function getType() :int
    {
        $type = $this->getAttribute(self::TYPE_ATTRIBUTE, false);
        
        if ( ! is_int($type)) {
            throw RequestHasNoType::becauseTheTypeIsNotAnInteger($type);
        }
        
        if ($type < 1 || $type > 3) {
            throw RequestHasNoType::becauseTheRangeIsNotCorrect($type);
        }
        
        return $type;
    }
    
}