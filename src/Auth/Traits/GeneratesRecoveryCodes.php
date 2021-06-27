<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use Illuminate\Support\Collection;
    use WPEmerge\Auth\RecoveryCode;

    trait GeneratesRecoveryCodes
    {


        private function generateNewRecoveryCodes () :string
        {
            return $this->encryptor->encrypt(json_encode(Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all()));

        }

        public function saveCodes(int $user_id, string $encrypted_codes) {

            update_user_meta($user_id, 'two_factor_recovery_codes', $encrypted_codes);

        }

    }