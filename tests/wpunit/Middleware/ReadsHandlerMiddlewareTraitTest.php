<?php

namespace WPEmergeTests\Middleware;

use Mockery;
use PHPUnit\Framework\TestCase;
use WPEmerge\Helpers\Handler;
use WPEmerge\Contracts\HasControllerMiddlewareInterface;
use WPEmerge\Middleware\ReadsHandlerMiddlewareTrait;

/**
 * @coversDefaultClass \WPEmerge\Middleware\ReadsHandlerMiddlewareTrait
 */
class ReadsHandlerMiddlewareTraitTest extends TestCase {
	public function setUp() :void  {
		parent::setUp();

		$this->subject = new ReadsHandlerMiddlewareTraitImplementation();
	}

	public function tearDown() :void  {
		parent::tearDown();
		Mockery::close();

		unset( $this->subject );
	}

	/**
	 * @covers ::getHandlerMiddleware
	 */
	public function testGetHandlerMiddleware_NonControllerMiddlewareHandler_EmptyArray() {

		$handler = Mockery::mock( Handler::class );

		$handler->shouldReceive( 'make' )
			->andReturn( function () {} );

		$handler->shouldReceive('controllerMiddleware')->once()->andReturn([]);


		$this->assertEquals( [], $this->subject->publicGetHandlerMiddleware( $handler ) );
	}

	/**
	 * @covers ::getHandlerMiddleware
	 */
	public function testGetHandlerMiddleware_ControllerMiddlewareHandler_FullArray() {

		$handler = Mockery::mock( Handler::class );
		$instance = Mockery::mock( HasControllerMiddlewareInterface::class );

		$handler->shouldReceive('controllerMiddleware')->andReturn(['middleware1']);

		$handler->shouldReceive( 'make' )
			->andReturn( $instance );



		$this->assertEquals( ['middleware1'], $this->subject->publicGetHandlerMiddleware( $handler ) );
	}
}

class ReadsHandlerMiddlewareTraitImplementation {
	use ReadsHandlerMiddlewareTrait;

	public function publicGetHandlerMiddleware() {
		return call_user_func_array( [$this, 'getControllerMiddleware' ], func_get_args() );
	}
}
