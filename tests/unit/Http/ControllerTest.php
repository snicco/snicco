<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Http;

	use WPEmerge\Http\Controller;
	use PHPUnit\Framework\TestCase;

	class ControllerTest extends TestCase {


		/** @test */
		public function middleware_can_be_added_to_are_controller() {

			$subject = new TestController();

			$this->assertSame(['foo', 'baz'], $subject->getMiddleware());

		}

		/** @test */
		public function middleware_can_be_added_for_some_methods_only () {


			$subject = new TestController();

			$this->assertSame(['foo', 'bar', 'baz'], $subject->getMiddleware('foo_method'));


		}

		/** @test */
		public function middleware_can_be_added_for_all_methods_expect_some () {

			$subject = new TestController();

			$this->assertSame(['foo', 'bar', 'baz'], $subject->getMiddleware('foo_method'));

			$this->assertSame(['foo', 'baz'], $subject->getMiddleware('foo'));

			$this->assertSame(['foo'], $subject->getMiddleware('bar_method'));


		}

		/** @test */
		public function blacklist_and_whitelist_cant_be_combined () {

			$this->expectExceptionMessage('The only() method cant be combined with the except() method for one middleware');

			$subject = new InvalidController();

		}


	}




	class TestController extends Controller {


		public function __construct() {

			$this->middleware('foo');

			$this->middleware('bar')->only('foo_method');

			$this->middleware('baz')->except(['bar_method', 'biz_method']);

		}


	}

	class InvalidController extends Controller {

		public function __construct() {

			$this->middleware('bar')->only('foo_method')->except('bar');


		}

	}
