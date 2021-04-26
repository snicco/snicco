<?php


	namespace Tests\unit\Helpers;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Helpers\MixedType;


	class MixedTypeTest extends TestCase {

		public function callableStub( $message = 'foobar' ) {

			return $message;
		}

		/** @test */
		public function to_array_contains_the_original_value() {

			$parameter = 'foobar';
			$expected  = [ 'foobar' ];

			$this->assertEquals( $expected, MixedType::toArray( $parameter ) );
		}

		/** @test */
		public function to_array_does_nothing_when_provided_an_array() {

			$expected = [ 'foobar' ];

			$this->assertEquals( $expected, MixedType::toArray( $expected ) );
		}

		/** @test */
		public function normalize_path() {

			$ds    = DIRECTORY_SEPARATOR;
			$input = '/foo\\bar/baz\\\\foobar';

			$this->assertEquals( "{$ds}foo{$ds}bar{$ds}baz{$ds}foobar", MixedType::normalizePath( $input ) );
			$this->assertEquals( '/foo/bar/baz/foobar', MixedType::normalizePath( $input, '/' ) );
			$this->assertEquals( '\\foo\\bar\\baz\\foobar', MixedType::normalizePath( $input, '\\' ) );
		}

		/** @test */
		public function add_trailing_slash() {

			$input = '/foo';

			$this->assertEquals( "/foo/", MixedType::addTrailingSlash( $input, '/' ) );
		}

		/** @test */
		public function remove_trailing_slash() {

			$input = '/foo/';

			$this->assertEquals( "/foo", MixedType::removeTrailingSlash( $input, '/' ) );
		}

	}
