<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Traits;

    use Illuminate\Support\Collection;
    use WPMvc\Auth\RecoveryCode;

    trait GeneratesRecoveryCodes
    {


        private function generateNewRecoveryCodes () :array
        {

            return Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();

        }

        public function saveCodes(int $user_id, array $codes ) {

            $codes = $this->encryptor->encrypt(json_encode($codes));

            update_user_meta($user_id, 'two_factor_recovery_codes', $codes);

        }

    }