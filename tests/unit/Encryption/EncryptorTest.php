<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Encryption;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Contracts\EncryptorInterface;
	use WPEmerge\Encryption\Encryptor;

	class EncryptorTest extends TestCase {

		const test_key = 'base64:yRYtcDAkaEYSR2T3qaYunXW+rxD6OgIWOdSVc34Hxdw=';

		protected function setUp() : void {

			parent::setUp();

		}


		/** @test */
		public function a_valid_encryptor_instance_can_be_created_with_base64_encoding() {


			$encryptor = new Encryptor( self::test_key );

			$this->assertInstanceOf( EncryptorInterface::class, $encryptor );

		}


		/** @test */
		public function an_encryptor_can_be_created_without_base64_encoding() {

			$encryptor = new Encryptor( str_repeat('a', 32) );

			$encrypted = $encryptor->encrypt( 'foo' );

			$this->assertNotSame( 'foo', $encrypted );

			$this->assertSame( 'foo', $encryptor->decrypt( $encrypted ) );

		}



	}
