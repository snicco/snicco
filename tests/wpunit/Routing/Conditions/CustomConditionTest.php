<?php

namespace Tests\wpunit\Routing\Conditions;

use Codeception\TestCase\WPTestCase;
use Mockery;
use WPEmerge\Routing\Conditions\CustomCondition;
use WPEmerge\Contracts\RequestInterface;

/**
 * @coversDefaultClass \WPEmerge\Routing\Conditions\CustomCondition
 */
class CustomConditionTest extends WPTestCase {
	/**
	 * @covers ::isSatisfied
	 */
	public function testIsSatisfied() {

		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

		$subject1 = new CustomCondition( '__return_true' );
		$this->assertTrue( $subject1->isSatisfied( $request ) );

		$subject2 = new CustomCondition( '__return_false' );
		$this->assertFalse( $subject2->isSatisfied( $request ) );
	}

	/**
	 * @covers ::getArguments
	 */
	public function testGetArguments() {
		$callable = function() {};
		$arguments = ['foo', 'bar'];
		$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

		$subject = new CustomCondition( $callable, $arguments[0], $arguments[1] );

		$this->assertEquals( $arguments, $subject->getArguments( $request ) );
	}
}
