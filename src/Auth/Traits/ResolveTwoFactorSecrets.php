<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use WP_User;

    use function get_user_meta;
    use function update_user_meta;

    trait ResolveTwoFactorSecrets
    {

        public function twoFactorSecret(int $user_id ) :string {

            $secret = get_user_meta($user_id, 'two_factor_secret', true);

            if ( ! $secret  ) {
                return '';
            }

            return $secret;

        }

        public function recoveryCodes ( int $user_id ) :string {

            return get_user_meta($user_id, 'two_factor_recovery_codes', true);

        }

        public function updateRecoveryCodes( int $user_id , string $codes) {

            update_user_meta($user_id, 'two_factor_recovery_codes', $codes);

        }

        public function userHasTwoFactorEnabled(WP_User $user) : bool
        {

            return $this->twoFactorSecret($user->ID) !== '';

        }

    }