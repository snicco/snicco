<?php

declare(strict_types=1);

namespace Snicco\Http\Psr7;

use Snicco\Http\Cookie;
use Snicco\Http\Cookies;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Contracts\Responsable;
use Psr\Http\Message\StreamFactoryInterface;

class Response implements ResponseInterface, Responsable
{
    
    use ImplementsPsr7Response;
    
    protected ?Cookies $cookies = null;
    
    protected StreamFactoryInterface $response_factory;
    
    public function __construct(ResponseInterface $psr7_response)
    {
        $this->psr7_response = $psr7_response;
        $this->cookies =
            ($psr7_response instanceof Response) ? $psr7_response->cookies() : new Cookies();
    }
    
    public function toResponsable()
    {
        return $this;
    }
    
    public function noIndex(?string $bot = null) :self
    {
        $value = $bot ? $bot.': noindex' : 'noindex';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function noFollow(?string $bot = null) :self
    {
        $value = $bot ? $bot.': nofollow' : 'nofollow';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function noRobots(?string $bot = null) :self
    {
        $value = $bot ? $bot.': none' : 'none';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function noArchive(?string $bot = null) :self
    {
        $value = $bot ? $bot.': noarchive' : 'noarchive';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    // makes no sense to copy the object here since we can't
    // change the object property reference anyway without extra libraries.
    public function withCookie(Cookie $cookie) :self
    {
        $this->cookies->set($cookie->name(), $cookie->properties());
        return $this;
    }
    
    public function withoutCookie(string $name, string $path = '/') :Response
    {
        $cookie = new Cookie($name, 'deleted');
        $cookie->expires(1)->path($path);
        $this->cookies->add($cookie);
        return $this;
    }
    
    public function cookies() :Cookies
    {
        if ( ! $this->cookies) {
            $this->cookies = new Cookies();
        }
        
        return $this->cookies;
    }
    
    public function html(StreamInterface $html) :Response
    {
        return $this->withHeader('Content-Type', 'text/html')
                    ->withBody($html);
    }
    
    public function json(StreamInterface $json) :Response
    {
        return $this->withHeader('Content-Type', 'application/json')
                    ->withBody($json);
    }
    
    public function isRedirect(string $location = null) :bool
    {
        return in_array($this->getStatusCode(), [201, 301, 302, 303, 307, 308])
               && (null === $location || $location == $this->getHeader('Location'));
    }
    
    public function isSuccessful() :bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }
    
    public function isOk() :bool
    {
        return 200 === $this->getStatusCode();
    }
    
    public function isNotFound() :bool
    {
        return 404 === $this->getStatusCode();
    }
    
    public function isForbidden() :bool
    {
        return 403 === $this->getStatusCode();
    }
    
    public function isInformational() :bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }
    
    public function isRedirection() :bool
    {
        $status = $this->getStatusCode();
        return $status >= 300 && $status < 400;
    }
    
    public function isClientError() :bool
    {
        $status = $this->getStatusCode();
        return $status >= 400 && $status < 500;
    }
    
    public function isServerError() :bool
    {
        $status = $this->getStatusCode();
        return $status >= 500 && $status < 600;
    }
    
    public function isEmpty() :bool
    {
        return in_array($this->getStatusCode(), [204, 205, 304]);
    }
    
    public function hasEmptyBody() :bool
    {
        return (intval($this->getBody()->getSize())) === 0;
    }
    
    public function withContentType(string $content_type) :self
    {
        return $this->withHeader('content-type', $content_type);
    }
    
    protected function new(ResponseInterface $new_psr_response) :self
    {
        $new = clone $this;
        $new->setPsr7Response($new_psr_response);
        return $new;
    }
    
    protected function setPsr7Response(ResponseInterface $psr_response)
    {
        $this->psr7_response = $psr_response;
    }
    
}