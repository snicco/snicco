<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
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
        
        if ( ! $request->filled('user_id')) {
            
            throw new FailedAuthenticationException(
                "Failed Authentication with missing user_id param in magic link",
            );
            
        }
        
        $valid = $this->magic_link->hasValidSignature($request, true);
        
        if ( ! $valid) {
            
            throw new FailedAuthenticationException(
                "Failed Authentication with invalid magic-link for user [{{$request->query('user_id')}]",
            );
            
        }
        
        $this->magic_link->invalidate($request->fullUrl());
        $user = $this->getUserById($request->query('user_id'));
        
        if ( ! $user instanceof WP_User) {
            
            throw new FailedAuthenticationException(
                "Failed Authentication with magic-link for unresolvable user_id [{{$request->query('user_id')}]",
            );
            
        }
        
        // Whether remember_me will be allowed is determined by the config value in AuthSessionController
        return $this->login($user, true);
        
    }
    
}