<?php


	namespace Tests\wpunit\Input;

	use Mockery;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Session\FlashStore;
	use WPEmerge\Session\OldInputStore;


	class OldInputTest extends TestCase {

		public function setUp() : void {

			parent::setUp();

			$this->flash     = Mockery::mock( FlashStore::class );
			$this->flash_key = '__foobar';
			$this->subject   = new OldInputStore( $this->flash, $this->flash_key );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->subject );
			unset( $this->flash );
		}


		public function testEnabled() {

			$flash1 = Mockery::mock( FlashStore::class );
			$flash1->shouldReceive( 'enabled' )
			       ->andReturn( true );
			$subject1 = new OldInputStore( $flash1 );

			$this->assertTrue( $subject1->enabled() );

			$flash2 = Mockery::mock( FlashStore::class );
			$flash2->shouldReceive( 'enabled' )
			       ->andReturn( false );
			$subject2 = new OldInputStore( $flash2 );

			$this->assertFalse( $subject2->enabled() );
		}


		public function testGet() {

			$this->flash->shouldReceive( 'get' )
			            ->with( $this->flash_key, [] )
			            ->andReturn( [ 'foo' => 'foobar' ] );

			$this->assertEquals( 'foobar', $this->subject->get( 'foo' ) );
			$this->assertEquals( 'barbaz', $this->subject->get( 'bar', 'barbaz' ) );
		}


		public function testSet() {

			$this->flash->shouldReceive( 'add' )
			            ->with( $this->flash_key, [ 'foo' => 'foobar' ] )
			            ->once();

			$this->subject->set( [ 'foo' => 'foobar' ] );

			$this->assertTrue( true );
		}


		public function testClear() {

			$this->flash->shouldReceive( 'clear' )
			            ->with( $this->flash_key )
			            ->once();

			$this->subject->clear();

			$this->assertTrue( true );
		}

	}
