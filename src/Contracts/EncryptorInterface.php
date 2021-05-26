<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use WPEmerge\ExceptionHandling\Exceptions\DecryptException;
    use WPEmerge\ExceptionHandling\Exceptions\EncryptException;

    interface EncryptorInterface {

		/**
		 * Encrypt the given value.
		 *
		 * @param  mixed  $value
		 * @param  bool  $serialize
		 *
		 * @return string
		 *
		 * @throws EncryptException
		 */
		public function encrypt($value, bool $serialize = true) : string;

		/**
		 * Decrypt the given value.
		 *
		 * @param  string  $payload
		 * @param  bool  $unserialize
		 *
		 * @return mixed
		 *
		 * @throws DecryptException
		 */
		public function decrypt( string $payload, bool $unserialize = true);


		/**
		 * Encrypt a string without serialization.
		 *
		 * @param  string  $value
		 *
		 * @return string
		 *
		 * @throws EncryptException
		 */
		public function encryptString( string $value) : string;

		/**
		 * Decrypt the given string without unserialization.
		 *
		 * @param  string  $payload
		 *
		 * @return string
		 *
		 * @throws \WPEmerge\ExceptionHandling\Exceptions\DecryptException
		 */
		public function decryptString( string $payload) : string;

        /**
         * Create a new encryption key for the the AES-256-CBC cipher
         *
         * @return string
         */
		public static function generateKey() :string;

	}