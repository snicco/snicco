<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Helpers;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Support\Path;


	class PathTest extends TestCase {


		/** @test */
		public function normalize_path() {

			$ds    = DIRECTORY_SEPARATOR;
			$input = '/foo\\bar/baz\\\\foobar';

			$this->assertEquals( "{$ds}foo{$ds}bar{$ds}baz{$ds}foobar", Path::normalize( $input ) );
			$this->assertEquals( '/foo/bar/baz/foobar', Path::normalize( $input, '/' ) );
			$this->assertEquals( '\\foo\\bar\\baz\\foobar', Path::normalize( $input, '\\' ) );
		}

		/** @test */
		public function add_trailing_slash() {

			$input = '/foo';

			$this->assertEquals( "/foo/", Path::addTrailingSlash( $input, '/' ) );
		}

		/** @test */
		public function remove_trailing_slash() {

			$input = '/foo/';

			$this->assertEquals( "/foo", Path::removeTrailingSlash( $input, '/' ) );
		}

	}
