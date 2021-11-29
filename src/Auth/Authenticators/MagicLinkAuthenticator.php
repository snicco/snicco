<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Events\FailedMagicLinkAuthentication;

class MagicLinkAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    
    private MagicLink  $magic_link;
    private Dispatcher $dispatcher;
    
    public function __construct(MagicLink $magic_link, Dispatcher $dispatcher)
    {
        $this->magic_link = $magic_link;
        $this->dispatcher = $dispatcher;
    }
    
    public function attempt(Request $request, $next)
    {
        if ( ! $request->isGet() || ! $request->filled('signature')) {
            return $next($request);
        }
        
        $valid = $this->magic_link->hasValidSignature($request, true);
        $user_id = $request->query('user_id');
        
        if ( ! $valid || ! ($user = $this->getUserById($user_id)) instanceof WP_User) {
            $this->dispatcher->dispatch(
                new FailedMagicLinkAuthentication($request, (int) $user_id)
            );
            
            return $this->unauthenticated();
        }
        
        $this->magic_link->invalidate($request->fullUrl());
        
        // Whether remember_me will be allowed is determined by the config value in AuthSessionController
        return $this->login($user, true);
    }
    
}