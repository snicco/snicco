<?php


	namespace WPEmerge\Encryption;

	use Illuminate\Contracts\Encryption\EncryptException as IlluminateEncryptException;
	use Illuminate\Contracts\Encryption\DecryptException as IlluminateDecryptException;
	use Illuminate\Encryption\Encrypter;
	use WPEmerge\Contracts\EncryptorInterface;
	use WPEmerge\Exceptions\EncryptException;
	use WPEmerge\Exceptions\DecryptException;

	class Encryptor implements EncryptorInterface {

		/**
		 * @var \Illuminate\Encryption\Encrypter
		 */
		private $encryptor;

		public function __construct( $key, $cipher = 'AES-128-CBC') {

			$this->encryptor = new Encrypter($key, $cipher);

		}

		public function encrypt( $value, bool $serialize = true ) : string {

			try {

				return $this->encryptor->encrypt($value, $serialize);

			}
			catch ( IlluminateEncryptException $e ) {

				throw new EncryptException($e->getMessage());

			}


		}

		public function decrypt( string $payload, bool $unserialize = true ) {

			try {

				return $this->encryptor->decrypt($payload, $unserialize);

			}
			catch ( IlluminateDecryptException $e ) {

				throw new DecryptException($e->getMessage());

			}


		}

		public function encryptString( string $value ) : string {

			try {

				return $this->encryptor->encrypt($value, false);

			}
			catch ( IlluminateEncryptException $e ) {

				throw new EncryptException($e->getMessage());

			}

		}

		public function decryptString( string $payload ) : string {

			try {

				return $this->encryptor->decrypt($payload, false);

			}
			catch ( IlluminateDecryptException $e ) {

				throw new DecryptException($e->getMessage());

			}

		}

	}