<?php

namespace Tests\wpunit\Responses;

use Mockery;
use PHPUnit\Framework\TestCase;
use WPEmerge\Traits\HasControllerMiddleware;

/**
 * @coversDefaultClass \WPEmerge\Traits\HasControllerMiddleware
 */
class HasControllerMiddlewareTest extends TestCase {

	
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject
	 */
	private $subject;

	public function setUp() :void  {
		parent::setUp();

		$this->subject = $this->getMockForTrait( HasControllerMiddleware::class );
	}

	public function tearDown():void  {
		parent::tearDown();
		Mockery::close();

		unset( $this->subject );
	}

	/**
	 * @covers ::addMiddleware
	 * @covers ::getMiddleware
	 */
	public function testGetMiddleware() {

		$this->subject->addMiddleware( 'foo' );
		$this->subject->addMiddleware( 'bar' )->only( 'method2' );
		$this->subject->addMiddleware( ['baz'] )->except( 'method3' );

		$this->assertEquals( ['foo', 'baz'], $this->subject->getMiddleware( 'method1' ) );
		$this->assertEquals( ['foo', 'bar', 'baz'], $this->subject->getMiddleware( 'method2' ) );
		$this->assertEquals( ['foo'], $this->subject->getMiddleware( 'method3' ) );
	}
}
