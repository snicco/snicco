<?php

declare(strict_types=1);

namespace Snicco\Session\Middleware;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Session\Exceptions\InvalidCsrfTokenException;

class VerifyCsrfToken extends Middleware
{
    
    const TOKEN_KEY = '_token';
    
    /**
     * The URIs that should be excluded from CSRF verification.
     * Can a full url or a path. /* can be used to indicate a WildCard.
     *
     * @var string[]
     */
    protected array $except = [];
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $this->checkCsrfToken($request)) {
            return $next($request);
        }
        
        if ( ! $this->tokensMatch($request)) {
            throw new InvalidCsrfTokenException('Failed CSRF Check');
        }
        
        return $next($request);
    }
    
    private function checkCsrfToken(Request $request) :bool
    {
        if ($request->isMethodSafe()) {
            return false;
        }
        
        foreach ($this->except as $except) {
            if ($request->fullUrlIs($except) || $request->pathIs($except)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function tokensMatch(Request $request) :bool
    {
        $token = $request->input(self::TOKEN_KEY) ? : $request->getHeaderLine('X-CSRF-TOKEN', '');
        
        return is_string($request->session()->csrfToken())
               && is_string($token)
               && hash_equals($request->session()->csrfToken(), $token);
    }
    
}