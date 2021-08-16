<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Traits\ResolvesUser;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Contracts\EncryptorInterface;
use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
use Snicco\Auth\Exceptions\FailedTwoFactorAuthenticationException;

class TwoFactorAuthenticator extends Authenticator
{
    
    use ResolvesUser;
    use PerformsTwoFactorAuthentication;
    
    private TwoFactorAuthenticationProvider $provider;
    private EncryptorInterface              $encryptor;
    private array                           $recovery_codes = [];
    
    public function __construct(TwoFactorAuthenticationProvider $provider, EncryptorInterface $encryptor)
    {
        $this->provider = $provider;
        $this->encryptor = $encryptor;
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
            throw new FailedTwoFactorAuthenticationException(
                "Failed Authentication with invalid two factor code for user [$user_id]",
            );
        }
        
        $remember = $session->get('auth.2fa.remember', false);
        $session->forget('auth.2fa');
        
        return $this->login($user, $remember);
    }
    
}