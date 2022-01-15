<?php

namespace Snicco\Auth\Controllers;

use Snicco\Auth\Traits\ResolvesUser;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Core\Shared\Encryptor;
use Snicco\HttpRouting\Http\AbstractController;
use Snicco\Auth\Traits\InteractsWithTwoFactorCodes;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthSetupAbstractController extends AbstractController
{
    
    use ResolvesUser;
    use InteractsWithTwoFactorSecrets;
    use InteractsWithTwoFactorCodes;
    
    private TwoFactorAuthenticationProvider $provider;
    
    public function __construct(TwoFactorAuthenticationProvider $provider, Encryptor $encryptor)
    {
        $this->provider = $provider;
        $this->encryptor = $encryptor;
    }
    
    public function store(Request $request)
    {
        $user = $request->user();
        
        $this->saveSecret($user->ID, $secret = $this->provider->generateSecretKey());
        $this->mark2FaSetupAsPending($user->ID);
        
        return $this->response_factory->json([
            'secret' => $secret,
            'qr_code' => $this->provider->renderQrCode(),
        ]);
    }
    
}