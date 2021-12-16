<?php

declare(strict_types=1);

namespace Snicco\Core\Http\Psr7;

use Snicco\Core\Http\Cookie;
use Snicco\Core\Http\Cookies;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Response implements ResponseInterface
{
    
    use ImplementsPsr7Response;
    
    /**
     * @var Cookies
     */
    private $cookies;
    
    private $flash_messages = [];
    
    private $old_input = [];
    
    private $errors = [];
    
    public function __construct(ResponseInterface $psr7_response)
    {
        $this->psr7_response = $psr7_response;
        $this->cookies =
            ($psr7_response instanceof Response) ? $psr7_response->cookies() : new Cookies();
    }
    
    public function withNoIndex(?string $bot = null) :self
    {
        $value = $bot ? $bot.': noindex' : 'noindex';
    
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function withNoFollow(?string $bot = null) :self
    {
        $value = $bot ? $bot.': nofollow' : 'nofollow';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function withNoRobots(?string $bot = null) :self
    {
        $value = $bot ? $bot.': none' : 'none';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function withNoArchive(?string $bot = null) :self
    {
        $value = $bot ? $bot.': noarchive' : 'noarchive';
        
        return $this->withAddedHeader('X-Robots-Tag', $value);
    }
    
    public function withContentType(string $content_type) :self
    {
        return $this->withHeader('content-type', $content_type);
    }
    
    public function withCookie(Cookie $cookie) :self
    {
        $response = clone $this;
        $response->cookies->add($cookie);
        
        return $response;
    }
    
    public function withoutCookie(string $name, string $path = '/') :self
    {
        $cookie = new Cookie($name, 'deleted');
        $cookie = $cookie->withExpiryTimestamp(1)->withPath($path);
        
        $response = clone $this;
        $response->cookies->add($cookie);
        
        return $response;
    }
    
    /**
     * @param  string|array  $key
     * @param  mixed  $value
     */
    public function withFlashMessages($key, $value = null) :self
    {
        $key = is_array($key) ? $key : [$key => $value];
        
        $flash_messages = $this->flash_messages;
        foreach ($key as $k => $v) {
            if ( ! is_string($k)) {
                throw new InvalidArgumentException('Keys have to be strings');
            }
            $flash_messages[$k] = $v;
        }
        
        $response = clone $this;
        
        $response->flash_messages = $flash_messages;
        
        return $response;
    }
    
    /**
     * @param  string|array  $key
     * @param  mixed  $value
     */
    public function withOldInput($key, $value = null) :self
    {
        $input = is_array($key) ? $key : [$key => $value];
        $_input = $this->old_input;
        foreach ($input as $k => $v) {
            if ( ! is_string($k)) {
                throw new InvalidArgumentException('Keys have to be strings');
            }
            $_input[$k] = $v;
        }
        
        $response = clone $this;
        
        $response->old_input = $_input;
        
        return $response;
    }
    
    /**
     * @param  array<string,string>|<array<string,array<string>>  $errors
     */
    public function withErrors($errors, string $namespace = 'default') :self
    {
        $_errors = $this->errors;
        foreach ($errors as $key => $messages) {
            if ( ! is_string($key)) {
                throw new InvalidArgumentException("Keys have to be strings");
            }
            
            $messages = (array) $messages;
            foreach ($messages as $message) {
                $_errors[$namespace][$key][] = $message;
            }
        }
        
        $response = clone $this;
        
        $response->errors = $_errors;
        
        return $response;
    }
    
    public function cookies() :Cookies
    {
        if ( ! $this->cookies) {
            $this->cookies = new Cookies();
        }
        
        return $this->cookies;
    }
    
    public function html(StreamInterface $html) :self
    {
        return $this->withHeader('Content-Type', 'text/html; charset=UTF-8')
                    ->withBody($html);
    }
    
    public function json(StreamInterface $json) :self
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
    
    public function flashMessages() :array
    {
        return $this->flash_messages;
    }
    
    public function oldInput() :array
    {
        return $this->old_input;
    }
    
    public function errors() :array
    {
        return $this->errors;
    }
    
    public function __clone()
    {
        $this->cookies = clone $this->cookies;
    }
    
    protected function new(ResponseInterface $new_psr_response) :Response
    {
        $new = clone $this;
        $new->psr7_response = $new_psr_response;
        return $new;
    }
    
}