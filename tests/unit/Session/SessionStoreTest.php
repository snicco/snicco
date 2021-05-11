<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Session;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\Session\ArraySessionHandler;
	use WPEmerge\Session\SessionStore;

	use function serialize;

	class SessionStoreTest extends TestCase {

		/** @test */
		public function a_session_is_loaded_from_the_handler() {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertSame( 'baz', $session->get( 'not-present', 'baz' ) );
			$this->assertTrue( $session->has( 'foo' ) );
			$this->assertFalse( $session->has( 'bar' ) );
			$this->assertTrue( $session->isStarted() );

		}

		/** @test */
		public function session_attributes_are_merged_with_handler_attributes() {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->put( 'baz', 'biz' );
			$session->start();

			$this->assertSame( [ 'baz' => 'biz', 'foo' => 'bar' ], $session->all() );


		}

		/** @test */
		public function the_session_has_no_attributes_if_the_handler_doesnt() {

			$handler = $this->newArrayHandler( 10 );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$this->assertSame( [], $session->all() );

		}

		/** @test */
		public function a_session_id_can_be_migrated_without_destroying_the_data() {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$old_id = $session->getId();

			$this->assertTrue( $session->migrate() );
			$new_id = $session->getId();

			$this->assertNotEquals( $old_id, $new_id );
			$this->assertNotEmpty( $handler->read( $old_id ) );


		}

		/** @test */
		public function regenerate_is_an_alias_for_migrate () {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$old_id = $session->getId();

			$this->assertTrue( $session->regenerate() );
			$new_id = $session->getId();

			$this->assertNotEquals( $old_id, $new_id );
			$this->assertNotEmpty( $handler->read( $old_id ) );

		}

		/** @test */
		public function a_session_id_can_be_migrated_and_destroy_the_session_attributes() {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$old_id = $session->getId();

			$this->assertTrue( $session->migrate( true ) );
			$new_id = $session->getId();

			$this->assertNotEquals( $old_id, $new_id );
			$this->assertEmpty( $handler->read( $old_id ) );


		}

		/** @test */
		public function regenerate_can_also_destroy_old_session_data () {

			$handler = $this->newArrayHandler( 10 );
			$handler->write( $this->getSessionId(), \serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$old_id = $session->getId();

			$this->assertTrue( $session->regenerate( true ) );
			$new_id = $session->getId();

			$this->assertNotEquals( $old_id, $new_id );
			$this->assertEmpty( $handler->read( $old_id ) );


		}

		/** @test */
		public function a_session_is_properly_saved() {

			$session = $this->newSessionStore();
			$session->start();

			$session->put( '_flash.old', 'foo' );
			$session->put( '_flash.new', 'bar' );
			$session->put( 'baz', 'biz' );

			$this->assertEmpty( $session->getHandler()->read( $session->getId() ) );

			$session->save();

			$this->assertFalse( $session->isStarted() );
			$this->assertEmpty( $session->get( '_flash.new' ) );
			$this->assertSame( 'bar', $session->get( '_flash.old' ) );

			$this->assertEquals( [

				'baz'    => 'biz',
				'_flash' => [
					'old' => 'bar',
					'new' => [],
				],

			], \unserialize( $session->getHandler()->read( $session->getId() ) ) );

		}

		/** @test */
		public function a_session_is_saved_when_the_session_id_changed() {

			$handler = $this->newArrayHandler();
			$handler->write( $this->getSessionId(), serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$session->migrate();
			$new_id = $session->getId();

			$session->save();

			$this->assertSame( [
				'foo'    => 'bar',
				'_flash' => [
					'old' => [],
					'new' => [],
				],

			], unserialize( $handler->read( $new_id ) ) );

			$this->assertFalse( $session->isStarted() );

		}

		/** @test */
		public function all_session_attributes_can_be_retrieved() {

			$handler = $this->newArrayHandler();
			$handler->write( $this->getSessionId(), serialize( [ 'foo' => 'bar' ] ) );
			$session = $this->newSessionStore( $handler );
			$session->start();

			$this->assertSame( [ 'foo' => 'bar' ], $session->all() );

		}

		/** @test */
		public function only_a_partial_of_the_session_attributes_can_be_retrieved() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );
			$this->assertEquals( [ 'foo' => 'bar', 'baz' => 'biz' ], $session->all() );
			$this->assertEquals( [ 'baz' => 'biz' ], $session->only( [ 'baz' ] ) );

		}

		/** @test */
		public function key_existence_be_checked() {

			$session = $this->newSessionStore();

			$session->put( 'foo', 'bar' );
			$this->assertTrue( $session->exists( 'foo' ) );

			$session->put( 'baz', null );
			$session->put( 'hulk', [ 'one' => true ] );

			$this->assertTrue( $session->exists( 'baz' ) );
			$this->assertTrue( $session->exists( [ 'foo', 'baz' ] ) );
			$this->assertTrue( $session->exists( [ 'hulk.one' ] ) );

			$this->assertFalse( $session->exists( [ 'foo', 'baz', 'bogus' ] ) );
			$this->assertFalse( $session->exists( [ 'hulk.two' ] ) );
			$this->assertFalse( $session->exists( 'bogus' ) );

		}

		/** @test */
		public function it_can_be_checked_if_keys_are_missing() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', null );
			$session->put( 'hulk', [ 'one' => true ] );

			$this->assertTrue( $session->missing( 'bogus' ) );
			$this->assertTrue( $session->missing( [ 'foo', 'baz', 'bogus' ] ) );
			$this->assertTrue( $session->missing( [ 'hulk.two' ] ) );

			$this->assertFalse( $session->missing( 'foo' ) );
			$this->assertFalse( $session->missing( 'baz' ) );
			$this->assertFalse( $session->missing( [ 'foo', 'baz' ] ) );
			$this->assertFalse( $session->missing( [ 'hulk.one' ] ) );

		}

		/** @test */
		public function it_can_be_checked_that_keys_are_present_and_not_null() {


			$session = $this->newSessionStore();
			$session->put( 'foo', null );
			$session->put( 'bar', 'baz' );

			$this->assertTrue( $session->has( 'bar' ) );
			$this->assertFalse( $session->has( 'foo' ) );


		}

		/** @test */
		public function a_specific_key_can_be_retrieved_with_optional_default_value() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );

			$this->assertSame( 'bar', $session->get( 'foo', 'default' ) );
			$this->assertSame( 'default', $session->get( 'boo', 'default' ) );

		}

		/** @test */
		public function a_key_can_be_pulled_out_of_the_session_and_is_not_present_anymore_after() {

			$handler = $this->newArrayHandler();
			$handler->write( $this->getSessionId(), serialize( [
				'foo' => 'bar',
				'baz' => 'biz',
			] ) );
			$session = $this->newSessionStore( $handler );

			$session->start();

			$this->assertSame( [ 'foo' => 'bar', 'baz' => 'biz' ], $session->all() );
			$this->assertSame( 'biz', $session->pull( 'baz' ) );

			$this->assertSame( [ 'foo' => 'bar' ], $session->all() );

			$this->assertSame( 'default', $session->pull( 'bogus', 'default' ) );

			$this->assertSame( [ 'foo' => 'bar' ], $session->all() );


		}

		/** @test */
		public function it_can_be_checked_if_old_input_exists() {

			$session = $this->newSessionStore();

			$this->assertFalse( $session->hasOldInput() );

			$session->put( '_old_input', [ 'foo' => 'bar', 'bar' => 'baz', 'boo' => null ] );

			$this->assertTrue( $session->hasOldInput( 'foo' ) );
			$this->assertTrue( $session->hasOldInput( 'bar' ) );
			$this->assertFalse( $session->hasOldInput( 'biz' ) );
			$this->assertFalse( $session->hasOldInput( 'boo' ) );


		}

		/** @test */
		public function old_put_can_be_retrieved() {

			$session = $this->newSessionStore();

			$this->assertSame( [], $session->getOldInput() );

			$session->put( '_old_input', [ 'foo' => 'bar', 'bar' => 'baz', 'boo' => null ] );

			$this->assertSame( [
				'foo' => 'bar',
				'bar' => 'baz',
				'boo' => null,
			], $session->getOldInput() );

			$this->assertSame( 'bar', $session->getOldInput( 'foo' ) );
			$this->assertSame( 'baz', $session->getOldInput( 'bar' ) );
			$this->assertSame( null, $session->getOldInput( 'boo' ) );

			$this->assertSame( null, $session->getOldInput( 'boo', 'default' ) );
			$this->assertSame( 'default', $session->getOldInput( 'bogus', 'default' ) );


		}

		/** @test */
		public function session_attributes_can_be_replaced() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );
			$session->replace( [ 'foo' => 'baz' ] );
			$this->assertSame( 'baz', $session->get( 'foo' ) );
			$this->assertSame( 'biz', $session->get( 'baz' ) );

		}

		/** @test */
		public function a_key_can_be_remembered_and_stores_the_default_value_if_not_present() {

			$session = $this->newSessionStore();

			$result = $session->remember( 'foo', function () {

				return 'bar';
			} );
			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertSame( 'bar', $result );

			$session->put( 'baz', 'biz' );

			$result = $session->remember( 'baz', function () {

				$this->fail( 'This should not have been called' );

			} );

			$this->assertSame( 'biz', $result );

		}

		/** @test */
		public function a_value_can_be_pushed_onto_a_array_value() {

			$session = $this->newSessionStore();

			$session->put( 'foo', [ 'bar' ] );
			$session->push( 'foo', 'bar' );
			$session->push( 'foo', [ 'baz' => 'biz' ] );

			$this->assertSame( [ 'bar', 'bar', [ 'baz' => 'biz' ] ], $session->get( 'foo' ) );

		}

		/** @test */
		public function an_integer_value_can_be_incremented() {

			$session = $this->newSessionStore();

			$session->put( 'foo', 5 );
			$foo = $session->increment( 'foo' );
			$this->assertEquals( 6, $foo );
			$this->assertEquals( 6, $session->get( 'foo' ) );

			$foo = $session->increment( 'foo', 4 );
			$this->assertEquals( 10, $foo );
			$this->assertEquals( 10, $session->get( 'foo' ) );

			$this->assertEquals( 0, $session->get( 'bar' ) );
			$session->increment( 'bar' );
			$this->assertEquals( 1, $session->get( 'bar' ) );


		}

		/** @test */
		public function an_integer_value_can_be_decremented() {

			$session = $this->newSessionStore();

			$session->put( 'foo', 5 );
			$foo = $session->decrement( 'foo' );
			$this->assertEquals( 4, $foo );
			$this->assertEquals( 4, $session->get( 'foo' ) );

			$foo = $session->decrement( 'foo', 4 );
			$this->assertEquals( 0, $foo );
			$this->assertEquals( 0, $session->get( 'foo' ) );

			$this->assertEquals( 0, $session->get( 'bar' ) );
			$session->decrement( 'bar' );
			$this->assertEquals( - 1, $session->get( 'bar' ) );


		}

		/** @test */
		public function a_value_can_be_flashed_for_the_next_request() {

			$session = $this->newSessionStore();
			$session->flash( 'foo', 'bar' );
			$session->flash( 'bar', 0 );
			$session->flash( 'baz' );

			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertSame( 0, $session->get( 'bar' ) );
			$this->assertSame( true, $session->get( 'baz' ) );

			$session->save();

			$this->assertTrue( $session->has( 'foo' ) );
			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertEquals( 0, $session->get( 'bar' ) );

			$session->save();

			$this->assertFalse( $session->has( 'foo' ) );
			$this->assertNull( $session->get( 'foo' ) );

		}

		/** @test */
		public function data_can_be_flashed_to_the_current_request() {

			$session = $this->newSessionStore();
			$session->now( 'foo', 'bar' );
			$session->now( 'bar', 0 );

			$this->assertTrue( $session->has( 'foo' ) );
			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertEquals( 0, $session->get( 'bar' ) );

			$session->save();

			$this->assertFalse( $session->has( 'foo' ) );
			$this->assertNull( $session->get( 'foo' ) );

		}

		/** @test */
		public function session_data_can_be_reflashed() {


			$session = $this->newSessionStore();
			$session->flash( 'foo', 'bar' );
			$session->put( '_flash.old', [ 'foo' ] );
			$session->reflash();
			$this->assertNotFalse( array_search( 'foo', $session->get( '_flash.new' ) ) );
			$this->assertFalse( array_search( 'foo', $session->get( '_flash.old' ) ) );


		}

		/** @test */
		public function reflash_can_be_combined_with_now() {

			$session = $this->newSessionStore();
			$session->now( 'foo', 'bar' );
			$session->reflash();
			$this->assertNotFalse( array_search( 'foo', $session->get( '_flash.new' ) ) );
			$this->assertFalse( array_search( 'foo', $session->get( '_flash.old' ) ) );
		}

		/** @test */
		public function old_input_can_be_flashed() {


			$session = $this->newSessionStore();
			$session->put( 'boom', 'baz' );
			$session->flashInput( [ 'foo' => 'bar', 'bar' => 0 ] );

			$this->assertTrue( $session->hasOldInput( 'foo' ) );
			$this->assertSame( 'bar', $session->getOldInput( 'foo' ) );
			$this->assertEquals( 0, $session->getOldInput( 'bar' ) );
			$this->assertFalse( $session->hasOldInput( 'boom' ) );

			$session->save();

			$this->assertTrue( $session->hasOldInput( 'foo' ) );
			$this->assertSame( 'bar', $session->getOldInput( 'foo' ) );
			$this->assertEquals( 0, $session->getOldInput( 'bar' ) );
			$this->assertFalse( $session->hasOldInput( 'boom' ) );

		}

		/** @test */
		public function flashed_data_can_be_merged() {

			$session = $this->newSessionStore();
			$session->flash( 'foo', 'bar' );
			$session->put( 'fu', 'baz' );
			$session->put( '_flash.old', [ 'qu' ] );
			$this->assertNotFalse( array_search( 'foo', $session->get( '_flash.new' ) ) );
			$this->assertFalse( array_search( 'fu', $session->get( '_flash.new' ) ) );
			$session->keep( [ 'fu', 'qu' ] );
			$this->assertNotFalse( array_search( 'foo', $session->get( '_flash.new' ) ) );
			$this->assertNotFalse( array_search( 'fu', $session->get( '_flash.new' ) ) );
			$this->assertNotFalse( array_search( 'qu', $session->get( '_flash.new' ) ) );
			$this->assertFalse( array_search( 'qu', $session->get( '_flash.old' ) ) );
		}

		/** @test */
		public function remove_is_an_alias_for_pull() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );

			$pulled = $session->remove( 'foo' );

			$this->assertSame( 'bar', $pulled );
			$this->assertSame( 'biz', $session->get( 'baz' ) );
			$this->assertFalse( $session->has( 'foo' ) );

		}

		/** @test */
		public function attributes_can_be_forgotten_by_key() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );
			$session->put( 'boo', [ 'boom', 'bang' => 'bam' ] );

			$this->assertSame( 'bar', $session->get( 'foo' ) );
			$this->assertSame( 'biz', $session->get( 'baz' ) );
			$this->assertSame( [ 'boom', 'bang' => 'bam' ], $session->get( 'boo' ) );
			$this->assertSame( 'bam', $session->get( 'boo.bang' ) );

			$session->forget( 'foo' );
			$session->forget( 'boo.bang' );

			$this->assertFalse( $session->exists( 'foo' ) );
			$this->assertTrue( $session->exists( 'baz' ) );
			$this->assertTrue( $session->exists( 'boo' ) );

			$this->assertSame( [ 'boom' ], $session->get( 'boo' ) );


		}

		/** @test */
		public function the_entire_session_can_be_flushed() {

			$session = $this->newSessionStore();
			$session->put( 'foo', 'bar' );
			$session->put( 'baz', 'biz' );
			$session->put( 'boo', [ 'boom', 'bang' => 'bam' ] );

			$session->flush();

			$this->assertSame( [], $session->all() );

		}

		/** @test */
		public function the_entire_session_can_be_invalidated() {

			$session = $this->newSessionStore();
			$old_id  = $session->getId();

			$session->put( 'foo', 'bar' );
			$this->assertGreaterThan( 0, count( $session->all() ) );

			$session->save();

			$this->assertArrayHasKey( 'foo', unserialize( $session->getHandler()
			                                                      ->read( $old_id ) ) );

			$this->assertTrue( $session->invalidate() );

			$this->assertNotEquals( $old_id, $session->getId() );
			$this->assertCount( 0, $session->all() );
			$this->assertEquals( '', $session->getHandler()->read( $old_id ) );


		}

		/** @test */
		public function its_not_possible_to_set_an_invalid_session_id() {

			$session = $this->newSessionStore();
			$this->assertTrue( $session->isValidId( $session->getId() ) );

			$session->setId( 'null' );
			$this->assertNotNull( $session->getId() );
			$this->assertTrue( $session->isValidId( $session->getId() ) );

			$session->setId( str_repeat( 'a', 41 ) );
			$this->assertNotSame( str_repeat( 'a', 41 ), $session->getId() );

			$session->setId( str_repeat( 'a', 40 ) );
			$this->assertSame( str_repeat( 'a', 40 ), $session->getId() );

			$session->setId( 'wrong' );
			$this->assertNotSame( 'wrong', $session->getId() );

		}

		/** @test */
		public function a_session_can_be_named () {

			$session = $this->newSessionStore();
			$this->assertEquals( $session->getName(), $this->getSessionName() );
			$session->setName( 'foo' );
			$this->assertSame( 'foo', $session->getName() );


		}


		/** @test */
		public function the_previous_url_can_be_set () {

			$session = $this->newSessionStore();
			$this->assertEquals( null , $session->previousUrl() );

			$session->setPreviousUrl( 'https.//foo.com' );
			$this->assertSame( 'https.//foo.com', $session->previousUrl() );

		}






		private function newSessionStore( \SessionHandlerInterface $handler = null ) : SessionStore {

			return new SessionStore(
				$this->getSessionName(),
				$handler ?? new ArraySessionHandler( 10 ),
				$this->getSessionId()
			);
		}

		private function newArrayHandler( int $minutes = 10 ) : ArraySessionHandler {

			return new ArraySessionHandler( $minutes );

		}

		private function getSessionId() : string {

			return 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
		}

		private function getSessionName() : string {

			return 'name';
		}

	}