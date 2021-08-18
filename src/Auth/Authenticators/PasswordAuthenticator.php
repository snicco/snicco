<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Events\FailedPasswordAuthentication;

class PasswordAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    
    public function attempt(Request $request, $next)
    {
        
        if ( ! $this->shouldHandle($request)) {
            return $next($request);
        }
        
        $login = $request->post('log');
        $password = $request->post('pwd');
        
        if ( ! ($user = $this->getUserByLogin($login)) instanceof WP_User) {
            
            FailedPasswordAuthentication::dispatch([$request, $login, $password, $user_id = null]);
            
            return $this->unauthenticated();
            
        }
        
        if ( ! $this->isValidPassword($password, $user)) {
            
            FailedPasswordAuthentication::dispatch([$request, $login, $password, $user->ID]);
            
            return $this->unauthenticated();
            
        }
        
        return $this->login($user, $request->boolean('remember_me'));
        
    }
    
    private function shouldHandle(Request $request) :bool
    {
        return $request->filled('pwd') && $request->filled('log');
    }
    
    private function isValidPassword(string $password, WP_User $user) :bool
    {
        return wp_check_password($password, $user->user_pass, $user->ID);
    }
    
}