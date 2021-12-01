<?php

declare(strict_types=1);

namespace Snicco\Auth\Confirmation;

use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthConfirmation implements AuthConfirmation
{
    
    use PerformsTwoFactorAuthentication;
    
    private AuthConfirmation                $fallback;
    private TwoFactorAuthenticationProvider $provider;
    private ResponseFactory                 $response_factory;
    private Encryptor                       $encryptor;
    private string                          $user_secret;
    private Abstract2FAuthConfirmationView  $response;
    
    public function __construct(
        AuthConfirmation $fallback,
        TwoFactorAuthenticationProvider $provider,
        ResponseFactory $response_factory,
        Encryptor $encryptor,
        Abstract2FAuthConfirmationView $response
    ) {
        $this->fallback = $fallback;
        $this->encryptor = $encryptor;
        $this->provider = $provider;
        $this->response_factory = $response_factory;
        $this->response = $response;
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
    
    public function viewResponse(Request $request) :ViewInterface
    {
        if ( ! $this->userHasTwoFactorEnabled($request->user())) {
            return $this->fallback->viewResponse($request);
        }
        
        return $this->response->toView($request);
    }
    
}