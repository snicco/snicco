<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Events\FailedMagicLinkAuthentication;
use Snicco\Auth\Exceptions\FailedAuthenticationException;

class MagicLinkAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    
    private MagicLink $magic_link;
    
    public function __construct(MagicLink $magic_link)
    {
        $this->magic_link = $magic_link;
    }
    
    public function attempt(Request $request, $next)
    {
    
        if ( ! $request->isGet() || ! $request->filled('signature')) {
        
            return $next($request);
        
        }
    
        $valid = $this->magic_link->hasValidSignature($request, true);
        $user_id = $request->query('user_id');
    
        if ( ! $valid || ! ($user = $this->getUserById($user_id)) instanceof WP_User) {
        
            FailedMagicLinkAuthentication::dispatch([$request, $user_id]);
        
            return $this->unauthenticated();
        
        }
    
        $this->magic_link->invalidate($request->fullUrl());
    
        // Whether remember_me will be allowed is determined by the config value in AuthSessionController
        return $this->login($user, true);
    
    }
    
}