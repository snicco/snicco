<?php

declare(strict_types=1);

namespace Snicco\Auth\Listeners;

use Snicco\Session\Session;
use Snicco\Auth\Events\SettingAuthCookie;

class GenerateNewAuthCookie
{
    
    private Session $current_session;
    
    public function __construct(Session $current_session)
    {
        $this->current_session = $current_session;
    }
    
    /**
     *  This filters the final return of @see wp_generate_auth_cookie()
     *  The logic is identical. We only replace the random key generated with the session token.
     *
     * @see wp_generate_password() with our CSPRNG generated session id.
     */
    public function handle(SettingAuthCookie $event)
    {
        $user = $event->user;
        $expiration = $event->expiration;
        $scheme = $event->scheme;
        $token = $this->current_session->getId();
        
        $pass_frag = substr($user->user_pass, 8, 4);
        
        $key = wp_hash($user->user_login.'|'.$pass_frag.'|'.$expiration.'|'.$token, $scheme);
        
        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        $hash = hash_hmac($algo, $user->user_login.'|'.$expiration.'|'.$token, $key);
        
        $event->cookie = $user->user_login.'|'.$expiration.'|'.$token.'|'.$hash;
    }
    
}