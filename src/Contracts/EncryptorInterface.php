<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface EncryptorInterface {

		/**
		 * Encrypt the given value.
		 *
		 * @param  mixed  $value
		 * @param  bool  $serialize
		 *
		 * @return string
		 *
		 * @throws \WPEmerge\Exceptions\EncryptException
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
		 * @throws \WPEmerge\Exceptions\DecryptException
		 */
		public function decrypt( string $payload, bool $unserialize = true);


		/**
		 * Encrypt a string without serialization.
		 *
		 * @param  string  $value
		 *
		 * @return string
		 *
		 * @throws \WPEmerge\Exceptions\EncryptException
		 */
		public function encryptString( string $value) : string;

		/**
		 * Decrypt the given string without unserialization.
		 *
		 * @param  string  $payload
		 *
		 * @return string
		 *
		 * @throws \WPEmerge\Exceptions\DecryptException
		 */
		public function decryptString( string $payload) : string;

		/**
		 * Create a new encryption key for the the AES-256-CBC cipher
		 *
		 * @param  string  $cipher
		 * @return string
		 */
		public static function generateKey() :string;

	}