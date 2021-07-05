<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Stubs;

    use BetterWP\Auth\Contracts\TwoFactorAuthenticationProvider;

    class TestTwoFactorProvider implements TwoFactorAuthenticationProvider
    {

        public function generateSecretKey($length = 16, $prefix = '') : string
        {

            return 'secret';
        }

        public function qrCodeUrl(string $company_name, string $user_identifier, string $secret)
        {
        }

        public function verifyOneTimeCode(string $secret, string $code) : bool
        {

            return $code === '123456';
        }

        public function renderQrCode() : string
        {

            return 'code';
        }

    }