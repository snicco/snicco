<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Exceptions\FailedAuthenticationException;

class PasswordAuthenticator extends Authenticator
{
    use ResolvesUser;
    
    public function attempt(Request $request, $next)
    {
        
        if ( ! $request->filled('pwd') || ! $request->filled('log')) {
            
            throw new FailedAuthenticationException(
                'Failed authentication with missing password or username',
            );
            
        }
        
        $this->findUser($request);
        
        $password = $request->post('pwd');
        $remember = $request->boolean('remember_me');
        
        $user = $this->findUser($request);
        
        if ( ! (wp_check_password($password, $user->user_pass, $user->ID))) {
            
            throw new FailedAuthenticationException(
                "Failed authentication for user [$user->ID] with wrong password [$password]",
                $request->only(['pwd', 'log', 'remember_me'])
            );
            
        }
        
        return $this->login($user, $remember);
        
    }
    
    private function findUser(Request $request) :WP_User
    {
    
        $user = $this->getUserByLogin($request->post('log'));
        
        if ( ! $user instanceof WP_User) {
            
            throw new FailedAuthenticationException(
                'Failed authentication for invalid username',
                $request->only(['pwd', 'log', 'remember_me'])
            );
            
        }
        
        return $user;
        
    }
    
}