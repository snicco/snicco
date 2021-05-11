<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Session;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Session\ArraySessionHandler;
	use Illuminate\Support\Carbon;

	class ArraySessionHandlerTest extends TestCase {

		/** @test */
		public function a_session_can_be_opened () {

			$handler = new ArraySessionHandler(10);

			$this->assertTrue($handler->open('', ''));

		}

		/** @test */
		public function a_session_can_be_closed () {

			$handler = new ArraySessionHandler(10);

			$this->assertTrue($handler->close());

		}

		/** @test */
		public function read_from_session_returns_empty_string_for_non_existing_id () {

			$handler = new ArraySessionHandler(10);

			$handler->write(1, 'foo');

			$this->assertSame('', $handler->read(2));

		}

		/** @test */
		public function data_can_be_read_from_the_session()
		{
			$handler = new ArraySessionHandler(10);

			$handler->write('foo', 'bar');

			$this->assertSame('bar', $handler->read('foo'));
		}

		/** @test */
		public function data_can_be_read_from_an_almost_expired_session()
		{
			$handler = new ArraySessionHandler(10);

			$handler->write('foo', 'bar');

			Carbon::setTestNow(Carbon::now()->addMinutes(10));
			$this->assertSame('bar', $handler->read('foo'));
			Carbon::setTestNow();
		}

		/** @test */
		public function reading_data_from_expired_sessions_returns_an_empty_string()
		{
			$handler = new ArraySessionHandler(10);

			$handler->write('foo', 'bar');

			Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSecond());
			$this->assertSame('', $handler->read('foo'));
			Carbon::setTestNow();
		}

		/** @test */
		public function data_can_be_written_to_the_session () {

			$handler = new ArraySessionHandler(10);

			$handler->write('foo', 'bar');
			$handler->write('foo', 'baz');

			$this->assertSame('baz', $handler->read('foo'));

		}

		/** @test */
		public function a_session_can_be_destroyed () {

			$handler = new ArraySessionHandler(10);

			$handler->write('foo', 'bar');

			$this->assertTrue($handler->destroy('foo'));

			$this->assertSame('', $handler->read('foo'));

		}

		/** @test */
		public function garbage_collection_works_for_old_sessions ()
		{
			$handler = new ArraySessionHandler(10);


			$handler->write('foo', 'bar');
			$this->assertTrue($handler->gc(300));
			$this->assertSame('bar', $handler->read('foo'));

			Carbon::setTestNow(Carbon::now()->addSecond());

			$handler->write('bar', 'baz');

			Carbon::setTestNow(Carbon::now()->addMinutes(5));

			$this->assertTrue($handler->gc(300));
			$this->assertSame('', $handler->read('foo'));
			$this->assertSame('baz', $handler->read('bar'));

			Carbon::setTestNow();
		}

	}
