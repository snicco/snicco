<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Http\Psr7\Request;

    /**
     * @property TwoFactorAuthenticationProvider $provider
     */
    trait PerformsTwoFactorAuthentication
    {

        use InteractsWithTwoFactorSecrets;
        use InteractsWithTwoFactorCodes;

        protected function validateTwoFactorAuthentication(Request $request, int $user_id)  :bool {

            if ( $code = $this->validRecoveryCode($request, $user_id ) ) {

                $this->replaceRecoveryCode($code,$user_id);
                return true;
            }

            if ( $this->hasValidOneTimeCode($request, $user_id) ) {
                return true;
            }

            return false;

        }

        private function validRecoveryCode(Request $request, $user_id)
        {

            $provided_code = $request->input('recovery-code');

            if ( ! $provided_code ) {
                return false;
            }

            $codes = $this->recoveryCodes( $user_id );

            if ( ! count($codes) ) {
                return false;
            }

            return collect( $codes )->first(function ($code) use ($provided_code) {
                return hash_equals($provided_code, $code) ? $code : null;
            });

        }

        private function replaceRecoveryCode($code, int $user_id)
        {

            $new_codes = str_replace($code, RecoveryCode::generate(), $this->recovery_codes);

            $this->saveCodes($user_id, $new_codes);

        }

        private function hasValidOneTimeCode(Request $request, int $user_id) : bool
        {

            if ( ! $request->filled('token') ) {
                return false;
            }

            $user_secret = $this->twoFactorSecret($user_id);

            return $this->provider->verifyOneTimeCode(
                $user_secret,
                $request->input('token')
            );

        }


    }