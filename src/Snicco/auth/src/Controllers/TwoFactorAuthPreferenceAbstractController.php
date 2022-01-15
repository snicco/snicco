<?php

declare(strict_types=1);

namespace Snicco\Auth\Controllers;

use Snicco\Auth\Traits\ResolvesUser;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Core\Shared\Encryptor;
use Snicco\HttpRouting\Http\AbstractController;
use Snicco\Auth\Traits\InteractsWithTwoFactorCodes;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorAuthPreferenceAbstractController extends AbstractController
{
    
    use ResolvesUser;
    use InteractsWithTwoFactorSecrets;
    use InteractsWithTwoFactorCodes;
    
    private TwoFactorAuthenticationProvider $provider;
    private Encryptor                       $encryptor;
    
    public function __construct(TwoFactorAuthenticationProvider $provider, Encryptor $encryptor)
    {
        $this->provider = $provider;
        $this->encryptor = $encryptor;
    }
    
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ( ! $this->userHasPending2FaSetup($user)) {
            return $this->response_factory->json(
                [
                    'message' => 'Two-Factor authentication is not set-up.',
                ],
                409
            );
        }
        
        $code = $request->body('one-time-code');
        
        if ( ! $code
             || ! $this->provider->verifyOneTimeCode(
                $this->twoFactorSecret($user),
                $code
            )) {
            return $this->response_factory->json(
                [
                    'message' => 'Invalid or missing code provided.',
                ],
                400
            );
        }
        
        $this->saveCodes($user->ID, $backup_codes = $this->generateNewRecoveryCodes());
        $this->activate2Fa($user->ID);
        
        return $this->response_factory->json($backup_codes);
    }
    
    public function destroy(Request $request)
    {
        $this->disableTwoFactorAuthentication($request->userId());
        
        return $this->response_factory->noContent();
    }
    
}