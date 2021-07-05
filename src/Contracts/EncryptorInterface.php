<?php


	declare( strict_types = 1 );


	namespace WPMvc\Contracts;

	use WPMvc\ExceptionHandling\Exceptions\DecryptException;
    use WPMvc\ExceptionHandling\Exceptions\EncryptException;

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
		 * @throws \WPMvc\ExceptionHandling\Exceptions\DecryptException
		 */
		public function decryptString( string $payload) : string;


	}