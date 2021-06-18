<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Session;

	use PHPUnit\Framework\TestCase;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
	use Illuminate\Support\Carbon;

	class ArraySessionDriverTest extends UnitTest {

		/** @test */
		public function a_session_can_be_opened () {

			$handler = new ArraySessionDriver(10);

			$this->assertTrue($handler->open('', ''));

		}

		/** @test */
		public function a_session_can_be_closed () {

			$handler = new ArraySessionDriver(10);

			$this->assertTrue($handler->close());

		}

		/** @test */
		public function read_from_session_returns_empty_string_for_non_existing_id () {

			$handler = new ArraySessionDriver(10);

			$handler->write(1, 'foo');

			$this->assertSame('', $handler->read(2));

		}

		/** @test */
		public function data_can_be_read_from_the_session()
		{
			$handler = new ArraySessionDriver(10);

			$handler->write('foo', 'bar');

			$this->assertSame('bar', $handler->read('foo'));
		}

		/** @test */
		public function data_can_be_read_from_an_almost_expired_session()
		{
			$handler = new ArraySessionDriver(10);

			$handler->write('foo', 'bar');

			Carbon::setTestNow(Carbon::now()->addMinutes(10));
			$this->assertSame('bar', $handler->read('foo'));
			Carbon::setTestNow();
		}

		/** @test */
		public function reading_data_from_expired_sessions_returns_an_empty_string()
		{
			$handler = new ArraySessionDriver(10);

			$handler->write('foo', 'bar');

			Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSecond());
			$this->assertSame('', $handler->read('foo'));
			Carbon::setTestNow();
		}

		/** @test */
		public function data_can_be_written_to_the_session () {

			$handler = new ArraySessionDriver(10);

			$handler->write('foo', 'bar');
			$handler->write('foo', 'baz');

			$this->assertSame('baz', $handler->read('foo'));

		}

		/** @test */
		public function a_session_can_be_destroyed () {

			$handler = new ArraySessionDriver(10);

			$handler->write('foo', 'bar');

			$this->assertTrue($handler->destroy('foo'));

			$this->assertSame('', $handler->read('foo'));

		}

		/** @test */
		public function garbage_collection_works_for_old_sessions ()
		{
			$handler = new ArraySessionDriver(10);

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

		/** @test */
		public function all_session_for_a_given_user_id_can_be_retrieved () {

            $handler = new ArraySessionDriver(10);
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('foo', 'bar');

            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('bar', 'baz');

            $handler->setRequest($this->newRequestWithUserId(2));
            $handler->write('biz', 'bam');

            $sessions = $handler->getAllByUserId(1);

            $this->assertContainsOnlyInstancesOf(\stdClass::class, $sessions);
            $this->assertCount(2, $sessions);
            $this->assertSame(1, $sessions[0]->user_id);
            $this->assertSame(1, $sessions[1]->user_id);
            $this->assertSame('foo', $sessions[0]->id);
            $this->assertSame('bar', $sessions[1]->id);


		}

		/** @test */
		public function all_sessions_but_the_one_with_the_provided_token_can_be_destroyed_for_the_user () {

            $handler = new ArraySessionDriver(10);
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('foo', 'bar');
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('bar', 'baz');
            $handler->setRequest($this->newRequestWithUserId(2));
            $handler->write('biz', 'bam');

            $handler->destroyOthersForUser('foo', 1);

            $this->assertCount(2, $handler->all());

            $this->assertSame('', $handler->read('bar'));
            $this->assertSame('bam', $handler->read('biz'));


		}

		/** @test */
		public function all_sessions_for_a_user_can_be_destroyed () {

            $handler = new ArraySessionDriver(10);
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('foo', 'bar');
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('bar', 'baz');
            $handler->setRequest($this->newRequestWithUserId(2));
            $handler->write('biz', 'bam');

            $handler->destroyAllForUser(1);

            $this->assertCount(1, $handler->all());

            $this->assertSame('', $handler->read('foo'));
            $this->assertSame('', $handler->read('bar'));
            $this->assertSame('bam', $handler->read('biz'));


        }

        /** @test */
        public function all_sessions_can_be_destroyed () {

            $handler = new ArraySessionDriver(10);
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('foo', 'bar');
            $handler->setRequest($this->newRequestWithUserId(1));
            $handler->write('bar', 'baz');
            $handler->setRequest($this->newRequestWithUserId(2));
            $handler->write('biz', 'bam');

            $handler->destroyAll();

            $this->assertSame([], $handler->all());


        }

		private function newRequestWithUserId(int $id) {
		    $request = TestRequest::from('GET', 'foo');

		    return $request->withUser($id);
        }

	}
