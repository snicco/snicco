<?php

declare(strict_types=1);

namespace Snicco\Auth\Confirmation;

use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\EncryptorInterface;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthConfirmation implements AuthConfirmation
{
    
    use PerformsTwoFactorAuthentication;
    
    private AuthConfirmation                $fallback;
    private TwoFactorAuthenticationProvider $provider;
    private ResponseFactory                 $response_factory;
    private EncryptorInterface              $encryptor;
    private string                          $user_secret;
    
    public function __construct(
        AuthConfirmation $fallback,
        TwoFactorAuthenticationProvider $provider,
        ResponseFactory $response_factory,
        EncryptorInterface $encryptor
    ) {
        $this->fallback = $fallback;
        $this->encryptor = $encryptor;
        $this->provider = $provider;
        $this->response_factory = $response_factory;
    }
    
    public function confirm(Request $request) :bool
    {
        
        if ( ! $this->userHasTwoFactorEnabled($request->user())) {
            return $this->fallback->confirm($request);
        }
        
        return $this->validateTwoFactorAuthentication(
            $this->provider,
            $request,
            $request->userId()
        );
        
    }
    
    public function viewResponse(Request $request)
    {
        
        if ( ! $this->userHasTwoFactorEnabled($request->user())) {
            
            return $this->fallback->viewResponse($request);
            
        }
        
        return $this->response_factory->view('auth-layout', [
            'view' => 'auth-two-factor-challenge',
            'post_to' => $request->path(),
        ]);
        
    }
    
}