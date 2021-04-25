<?php

namespace Tests\wpunit\Responses;

use Codeception\TestCase\WPTestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use WPEmerge\Middleware\UserLoggedOut;
use WPEmerge\Contracts\RequestInterface;
use WPEmerge\Responses\ResponseService;

/**
 * @coversDefaultClass \WPEmerge\Middleware\UserLoggedOut
 */
class UserLoggedOutMiddlewareTest extends WPTestCase {
	public function setUp() :void  {
		parent::setUp();

		$this->user_id = 0;
		$this->response_service = Mockery::mock( ResponseService::class );
		$this->response = Mockery::mock( ResponseInterface::class );
		$this->subject = new UserLoggedOut( $this->response_service );
	}

	public function tearDown() :void  {
		parent::tearDown();
		Mockery::close();

		wp_set_current_user( 0 );

		if ( $this->user_id !== 0 ) {
			wp_delete_user( $this->user_id );
		}

		unset( $this->response_service );
		unset( $this->response );
		unset( $this->subject );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_LoggedOut_ContinueRequest() {
		$next = function () { return true; };
		$request = Mockery::mock( RequestInterface::class );

		$this->assertTrue( $this->subject->handle( $request, $next ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_LoggedIn_DefaultToHomeUrl() {
		$request = Mockery::mock( RequestInterface::class );
		$next = function () {};
		$url = home_url();

		$this->user_id = $this->factory->user->create();
		wp_set_current_user( $this->user_id );

		$this->response_service->shouldReceive( 'redirect' )
			->andReturn( $this->response );

		$this->response->shouldReceive( 'to' )
			->with( $url )
			->andReturn( $this->response )
			->once();

		$this->assertSame( $this->response, $this->subject->handle( $request, $next ) );
	}

	/**
	 * @covers ::handle
	 */
	public function testHandle_LoggedIn_UseCustomUrl() {
		$request = Mockery::mock( RequestInterface::class );
		$next = function () {};
		$url = home_url( '/foo' );

		$this->user_id = $this->factory->user->create();
		wp_set_current_user( $this->user_id );

		$this->response_service->shouldReceive( 'redirect' )
			->andReturn( $this->response );

		$this->response->shouldReceive( 'to' )
			->with( $url )
			->andReturn( $this->response )
			->once();

		$this->assertSame( $this->response, $this->subject->handle( $request, $next, $url ) );
	}
}
