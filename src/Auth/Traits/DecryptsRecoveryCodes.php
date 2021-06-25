<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use WPEmerge\Contracts\EncryptorInterface;

    trait DecryptsRecoveryCodes
    {

        /** @var EncryptorInterface */
        private $encryptor;

        public function decrypt(string $codes) :array {

            return json_decode($this->encryptor->decrypt($codes), true);

        }

    }