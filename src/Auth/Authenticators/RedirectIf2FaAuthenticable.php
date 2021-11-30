<?php

declare(strict_types=1);

namespace Snicco\Auth\Authenticators;

use WP_User;
use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Auth\Contracts\Authenticator;
use Snicco\Auth\Responses\SuccessfulLoginResponse;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Auth\Contracts\AbstractTwoFactorChallengeResponse;

class RedirectIf2FaAuthenticable extends Authenticator
{
    
    use InteractsWithTwoFactorSecrets;
    
    private AbstractTwoFactorChallengeResponse $challenge_response;
    
    public function __construct(AbstractTwoFactorChallengeResponse $response, Encryptor $encryptor)
    {
        $this->challenge_response = $response;
        $this->encryptor = $encryptor;
    }
    
    public function attempt(Request $request, $next) :Response
    {
        $response = $next($request);
        
        if ( ! $response instanceof SuccessfulLoginResponse) {
            return $response;
        }
        
        if ( ! $this->userHasTwoFactorEnabled($user = $response->authenticateUser())) {
            return $response;
        }
        
        $this->challengeUser($request, $user);
        
        return $this->response_factory->toResponse(
            $this->challenge_response->forRequest($request)->toResponsable()
        );
    }
    
    private function challengeUser(Request $request, WP_User $user) :void
    {
        $request->session()->put('auth.2fa.challenged_user', $user->ID);
        $request->session()->put('auth.2fa.remember', $request->boolean('remember_me'));
    }
    
}