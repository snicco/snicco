<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Events\FailedTwoFactorAuthentication;
use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    use PerformsTwoFactorAuthentication;
    
    private TwoFactorAuthenticationProvider $provider;
    private Encryptor                       $encryptor;
    private Dispatcher                      $dispatcher;
    private array                           $recovery_codes = [];
    
    public function __construct(TwoFactorAuthenticationProvider $provider, Encryptor $encryptor, Dispatcher $dispatcher)
    {
        $this->provider = $provider;
        $this->encryptor = $encryptor;
        $this->dispatcher = $dispatcher;
    }
    
    public function attempt(Request $request, $next) :Response
    {
        $session = $request->session();
        $user_id = $session->challengedUser();
        
        if ( ! $user_id || ! $this->userHasTwoFactorEnabled($user = $this->getUserById($user_id))) {
            return $next($request);
        }
        
        $valid = $this->validateTwoFactorAuthentication(
            $this->provider,
            $request,
            $user_id
        );
        
        if ( ! $valid) {
            $this->dispatcher->dispatch(
                new FailedTwoFactorAuthentication($request, (int) $user_id)
            );
            
            return $this->response_factory->redirect()
                                          ->back()
                                          ->withErrors(['login' => 'Invalid code provided']);
        }
        
        $remember = $session->get('auth.2fa.remember', false);
        $session->forget('auth.2fa');
        
        return $this->login($user, $remember);
    }
    
}