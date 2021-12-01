<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use Snicco\Auth\RecoveryCode;

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
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = RecoveryCode::generate();
        }
        return $codes;
    }
    
}