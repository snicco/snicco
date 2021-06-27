<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Http\Psr7\Request;

    trait PerformsTwoFactorAuthentication
    {

        private function validRecoveryCode(Request $request, $user_id)
        {

            $provided_code = $request->input('recovery-code');

            if ( ! $provided_code ) {
                return false;
            }

            $codes = $this->recoveryCodes( $user_id );

            if ( ! $codes || empty($codes) ) {
                return false;
            }

            $this->recovery_codes = json_decode( $this->encryptor->decrypt($codes), true );

            return collect( $this->recovery_codes )->first(function ($code) use ($provided_code) {
                return hash_equals($provided_code, $code) ? $code : null;
            });

        }

        private function replaceRecoveryCode($code, int $user_id)
        {

            $new_codes = str_replace($code, RecoveryCode::generate(), $this->recovery_codes);

            $new_codes = $this->encryptor->encrypt(json_encode($new_codes));

            $this->updateRecoveryCodes($user_id, $new_codes);

        }

        private function hasValidOneTimeCode(Request $request, int $user_id) : bool
        {
            $token = $request->input('token');

            if ( ! $token ) {
                return false;
            }

            $user_secret = $this->twoFactorSecret($user_id);

            return $this->provider->verify($user_secret, $token);

        }

        private function validateTwoFactorAuthentication(Request $request, int $user_id)  :bool {

            if ( $code = $this->validRecoveryCode($request, $user_id ) ) {

                $this->replaceRecoveryCode($code,$user_id);
                return true;
            }

            if ( $this->hasValidOneTimeCode($request, $user_id) ) {
                return true;
            }

            return false;


        }

    }