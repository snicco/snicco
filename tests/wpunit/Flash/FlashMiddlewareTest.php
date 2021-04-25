<?php


	namespace Tests\wpunit\Flash;

	use Codeception\TestCase\WPTestCase;
	use Mockery;
	use WPEmerge\Flash\Flash;
	use WPEmerge\Flash\FlashMiddleware;
	use WPEmerge\Contracts\RequestInterface;

	/**
	 * @coversDefaultClass \WPEmerge\Flash\FlashMiddleware
	 */
	class FlashMiddlewareTest extends WPTestCase {

		public function setUp() : void {

			parent::setUp();

			$this->flash   = Mockery::mock( Flash::class );
			$this->subject = new FlashMiddleware( $this->flash );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->flash );
			unset( $this->subject );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_Disabled_Ignore() {

			$request = Mockery::mock( RequestInterface::class );

			$this->flash->shouldReceive( 'enabled' )
			            ->andReturn( false );
			$this->flash->shouldNotReceive( 'shift' );
			$this->flash->shouldNotReceive( 'save' );

			$result = $this->subject->handle( $request, function ( $request ) {

				return $request;
			} );
			$this->assertSame( $request, $result );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_Enabled_StoresAll() {

			$request = Mockery::mock( RequestInterface::class );

			$this->flash->shouldReceive( 'enabled' )
			            ->andReturn( true )
			            ->ordered();
			$this->flash->shouldReceive( 'shift' )
			            ->ordered();
			$this->flash->shouldReceive( 'save' )
			            ->ordered();

			$result = $this->subject->handle( $request, function ( $request ) {

				return $request;
			} );
			$this->assertSame( $request, $result );
		}

	}
