<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use WP_User;
use Snicco\Shared\Encryptor;
use Snicco\ExceptionHandling\Exceptions\DecryptException;

/**
 * @property Encryptor $encryptor
 */
trait InteractsWithTwoFactorSecrets
{
    
    /**
     * @param  WP_User|int  $user
     *
     * @return string
     * @throws DecryptException
     */
    private function twoFactorSecret($user) :string
    {
        $user_id = is_int($user)
            ? $user
            : $user->ID;
        $secret = get_user_meta($user_id, 'two_factor_secret', true);
        
        if ( ! $secret) {
            return '';
        }
        
        return $this->encryptor->decrypt($secret);
    }
    
    private function userHasTwoFactorEnabled(WP_User $user) :bool
    {
        return filter_var(
            get_user_meta($user->ID, 'two_factor_active', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }
    
    /**
     * @param  WP_User|int  $user
     *
     * @return bool
     */
    private function userHasPending2FaSetup($user) :bool
    {
        $id = is_int($user)
            ? $user
            : $user->ID;
        return filter_var(
            get_user_meta($id, 'two_factor_pending', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }
    
    private function saveSecret(int $user_id, string $secret)
    {
        $encrypted_secret = $this->encryptor->encrypt($secret);
        update_user_meta($user_id, 'two_factor_secret', $encrypted_secret);
    }
    
    private function activate2Fa(int $user_id)
    {
        update_user_meta($user_id, 'two_factor_active', true);
        delete_user_meta($user_id, 'two_factor_pending');
    }
    
    private function disableTwoFactorAuthentication(int $user_id)
    {
        delete_user_meta($user_id, 'two_factor_secret');
        delete_user_meta($user_id, 'two_factor_recovery_codes');
        delete_user_meta($user_id, 'two_factor_active');
    }
    
    /**
     * @param  WP_User|int  $user
     *
     * @return void
     */
    private function mark2FaSetupAsPending($user) :void
    {
        $id = is_int($user)
            ? $user
            : $user->ID;
        update_user_meta($id, 'two_factor_pending', true);
    }
    
}