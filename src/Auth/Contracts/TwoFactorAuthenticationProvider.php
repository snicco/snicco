<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Contracts;

    interface TwoFactorAuthenticationProvider
    {

        public function generateSecretKey($length = 16, $prefix = '') : string;

        public function qrCodeUrl(string $company_name, string $user_identifier, string $secret);

        public function verifyOneTimeCode(string $secret, string $code) :bool;

        public function renderQrCode() :string;


    }