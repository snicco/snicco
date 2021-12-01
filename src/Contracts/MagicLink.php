<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use RuntimeException;
use Snicco\Support\WP;
use Snicco\Http\Cookie;
use Snicco\Support\Carbon;
use Snicco\Http\Psr7\Request;
use InvalidArgumentException;
use Snicco\Traits\HasLottery;
use Snicco\Http\Psr7\Response;
use Snicco\Traits\InteractsWithTime;

abstract class MagicLink
{
    
    use HasLottery;
    use InteractsWithTime;
    
    public const QUERY_STRING_ID = 'signature';
    
    protected string  $app_key;
    protected Request $request;
    protected array   $lottery = [4, 100];
    
    public function setAppKey(string $app_key)
    {
        $this->app_key = $app_key;
    }
    
    public function setLottery(array $lottery)
    {
        if ( ! is_int($lottery[0]) || ! is_int($lottery[1])) {
            throw new InvalidArgumentException('Invalid lottery provided');
        }
        
        $this->lottery = $lottery;
    }
    
    public function invalidate(string $url)
    {
        parse_str(parse_url($url)['query'] ?? '', $query);
        $signature = $query[self::QUERY_STRING_ID] ?? '';
        
        $this->destroy($signature);
    }
    
    abstract public function destroy($signature);
    
    public function create(string $url, int $expires_at, Request $request) :string
    {
        $signature = $this->hash($url, $request);
        
        if ($this->hitsLottery($this->lottery)) {
            $this->gc();
        }
        
        $stored = $this->store($signature, $expires_at);
        
        if ( ! $stored) {
            throw new RuntimeException('Magic link could not be stored');
        }
        
        return $signature;
    }
    
    abstract public function gc() :bool;
    
    abstract public function store(string $signature, int $expires) :bool;
    
    public function hasAccessToRoute(Request $request) :bool
    {
        if ($request->hasSession()) {
            return $request->session()->canAccessRoute($request->fullPath());
        }
        
        $cookie = $request->cookies()->get($this->accessCookieName($request), '');
        
        return $cookie === $this->hash($request->fullPath(), $request)
               && $request->expires() > $this->currentTime();
    }
    
    public function withPersistentAccessToRoute(Response $response, Request $request) :Response
    {
        if ($request->hasSession()) {
            $request->session()
                    ->allowAccessToRoute($request->fullPath(), $request->query('expires'));
        }
        else {
            $response = $this->addAccessCookie($response, $request);
        }
        
        return $response;
    }
    
    public function hasValidRelativeSignature(Request $request) :bool
    {
        return $this->hasValidSignature($request);
    }
    
    public function hasValidSignature(Request $request, $absolute = false) :bool
    {
        return $this->hasCorrectSignature($request, $absolute)
               && ! $this->signatureHasExpired($request)
               && $this->notUsed($request);
    }
    
    abstract public function notUsed(Request $request) :bool;
    
    protected function hash(string $url, Request $request) :string
    {
        if ( ! $this->app_key) {
            throw new RuntimeException('App key not set.');
        }
        
        $salt = $this->app_key;
        
        return hash_hmac('sha256', $url, $salt);
    }
    
    private function accessCookieName(Request $request)
    {
        $id = WP::userId();
        $path = $request->fullPath();
        $agent = $request->userAgent();
        
        return hash_hmac('sha256', $id.$path.$agent, $this->app_key);
    }
    
    private function addAccessCookie(Response $response, Request $request) :Response
    {
        $value = $this->hash($request->fullPath(), $request);
        
        $cookie = new Cookie($this->accessCookieName($request), $value);
        $cookie->expires($request->expires())
               ->path($request->path())
               ->onlyHttp();
        
        return $response->withCookie($cookie);
    }
    
    private function hasCorrectSignature(Request $request, $absolute = true) :bool
    {
        $url = $absolute ? $request->url() : $request->path();
        
        $query_without_signature = preg_replace(
            '/(^|&)'.static::QUERY_STRING_ID.'=[^&]+/',
            '',
            $request->queryString()
        );
        
        $query_without_signature = ltrim($query_without_signature, '&');
        
        $signature = $this->hash($url.'?'.$query_without_signature, $request);
        
        $valid = hash_equals($signature, $request->query(self::QUERY_STRING_ID, ''));
        
        return $valid;
    }
    
    private function signatureHasExpired(Request $request) :bool
    {
        $expires = $request->query('expires', null);
        
        if ( ! $expires) {
            return false;
        }
        
        return Carbon::now()->getTimestamp() > (int) $expires;
    }
    
}