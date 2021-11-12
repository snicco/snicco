<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use Snicco\Auth\RecoveryCode;
use Illuminate\Support\Collection;

trait InteractsWithTwoFactorCodes
{
    
    public function recoveryCodes(int $user_id) :array
    {
        $encrypted_codes = get_user_meta($user_id, 'two_factor_recovery_codes', true);
        
        if ($encrypted_codes === "") {
            return [];
        }
        
        return json_decode($this->encryptor->decrypt($encrypted_codes), true);
    }
    
    public function saveCodes(int $user_id, array $codes)
    {
        $codes = $this->encryptor->encrypt(json_encode($codes));
        
        update_user_meta($user_id, 'two_factor_recovery_codes', $codes);
    }
    
    private function generateNewRecoveryCodes() :array
    {
        return Collection::times(8, function () {
            return RecoveryCode::generate();
        })->all();
    }
    
}