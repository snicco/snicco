<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Traits;

    use WP_User;
    use Snicco\Contracts\EncryptorInterface;

    /**
     * @property EncryptorInterface $encryptor
     */
    trait InteractsWithTwoFactorSecrets
    {
	
	    private function twoFactorSecret(int $user_id) :string
	    {
		
		    $secret = get_user_meta($user_id, 'two_factor_secret', true);
		
		    if( ! $secret ) {
			    return '';
		    }
		
		    return $this->encryptor->decryptString($secret);

        }

        private function userHasTwoFactorEnabled(WP_User $user) : bool
        {
            return $this->twoFactorSecret($user->ID) !== '';
        }

        private function saveSecret(int $user_id, string $secret)
        {
	
	        $encrypted_secret = $this->encryptor->encryptString($secret);
	        update_user_meta($user_id, 'two_factor_secret', $encrypted_secret);
	
        }

        private function disableTwoFactorAuthentication(int $user_id ) {

            delete_user_meta($user_id, 'two_factor_secret');
            delete_user_meta($user_id, 'two_factor_recovery_codes');

        }

    }