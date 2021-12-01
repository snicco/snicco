<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use Snicco\Auth\RecoveryCode;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

trait PerformsTwoFactorAuthentication
{
    
    use InteractsWithTwoFactorSecrets;
    use InteractsWithTwoFactorCodes;
    
    protected function validateTwoFactorAuthentication(TwoFactorAuthenticationProvider $provider, Request $request, int $user_id) :bool
    {
        if ($code = $this->validRecoveryCode($request, $user_id)) {
            $this->replaceRecoveryCode($code, $user_id);
            return true;
        }
        
        if ($this->hasValidOneTimeCode($provider, $request, $user_id)) {
            return true;
        }
        
        return false;
    }
    
    private function validRecoveryCode(Request $request, int $user_id)
    {
        $provided_code = $request->post('recovery-code');
        
        if ( ! $provided_code) {
            return false;
        }
        
        $codes = $this->recoveryCodes($user_id);
        
        if ( ! count($codes)) {
            return false;
        }
        
        foreach ($codes as $code) {
            if (hash_equals($provided_code, $code)) {
                return $provided_code;
            }
        }
        return null;
    }
    
    private function replaceRecoveryCode($code, int $user_id)
    {
        $new_codes = str_replace($code, RecoveryCode::generate(), $this->recoveryCodes($user_id));
        
        $this->saveCodes($user_id, $new_codes);
    }
    
    private function hasValidOneTimeCode(TwoFactorAuthenticationProvider $provider, Request $request, int $user_id) :bool
    {
        if ( ! $request->filled('one-time-code')) {
            return false;
        }
        
        $user_secret = $this->twoFactorSecret($user_id);
        
        return $provider->verifyOneTimeCode(
            $user_secret,
            $request->post('one-time-code')
        );
    }
    
}