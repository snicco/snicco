<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\MagicLink;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\ExceptionHandling\Exceptions\InvalidSignatureException;

/**
 * @tooo needs to be refactored without sessions.
 */
class ValidateSignature extends Middleware
{
    
    private string    $type;
    private MagicLink $magic_link;
    
    public function __construct(MagicLink $magic_link, string $type = 'relative')
    {
        $this->type = $type;
        $this->magic_link = $magic_link;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($this->magic_link->hasAccessToRoute($request)) {
            return $next($request);
        }
        
        $valid = $this->magic_link->hasValidSignature($request, $this->type === 'absolute');
        
        if ($valid) {
            $response = $next($request);
            
            $response = $this->magic_link->withPersistentAccessToRoute($response, $request);
            
            $this->magic_link->invalidate($request->fullUrl());
            
            return $response;
        }
        
        throw new InvalidSignatureException(
            "Failed signature check for path: [{$request->fullRequestTarget()}]"
        );
    }
    
}