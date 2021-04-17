<?php

namespace WPEmergeTests\View;

use PHPUnit\Framework\TestCase;
use WPEmerge\View\HasNameTrait;

/**
 * @coversDefaultClass \WPEmerge\View\HasNameTrait
 */
class HasNameTraitTest extends TestCase {
	/**
	 * @covers ::getName
	 * @covers ::setName
	 */
	public function testGetNameContext() {
		$subject = $this->getMockForTrait( HasNameTrait::class );
		$expected = 'foo';

		$subject->setName( $expected );
		$this->assertEquals( $expected, $subject->getName() );
	}
}
