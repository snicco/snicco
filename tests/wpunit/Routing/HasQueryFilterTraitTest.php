<?php


	namespace WPEmergeTests\Routing;

	use Mockery;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\CanFilterQueryInterface;
	use WPEmerge\Routing\HasQueryFilterTrait;
	use WP_UnitTestCase;

	/**
	 * @coversDefaultClass \WPEmerge\Routing\HasQueryFilterTrait
	 */
	class HasQueryFilterTraitTest extends TestCase {

		public function setUp() : void {

			parent::setUp();

			$this->subject = $this->getMockForTrait( HasQueryFilterTrait::class );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->subject );
		}

		/**
		 * @covers ::applyQueryFilter
		 */
		public function testApplyQueryFilter_NoFilter_Unfiltered() {

			$expected = [ 'unfiltered' ];
			$request  = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

			$this->assertEquals( $expected, $this->subject->applyQueryFilter( $request, $expected ) );
		}

		/**
		 * @covers ::applyQueryFilter
		 */
		public function testApplyQueryFilter_CanFilterQueryCondition_FilteredArray() {

			$arguments = [ 'arg1', 'arg2' ];
			$request   = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();
			$condition = Mockery::mock( CanFilterQueryInterface::class );

			$this->subject->method( 'getAttribute' )
			              ->withConsecutive(
				              [ 'query' ],
				              [ 'condition' ]
			              )
			              ->will( $this->onConsecutiveCalls(
				              function ( $query_vars, $arg1, $arg2 ) {

					              return array_merge( $query_vars, [ $arg1, $arg2 ] );
				              },
				              $condition
			              ) );

			$condition->shouldReceive( 'isSatisfied' )
			          ->andReturn( true );

			$condition->shouldReceive( 'getArguments' )
			          ->andReturn( $arguments );

			$this->assertEquals( [
				'arg0',
				'arg1',
				'arg2',
			], $this->subject->applyQueryFilter( $request, [ 'arg0' ] ) );
		}

		/**
		 * @covers ::applyQueryFilter
		 */
		public function testApplyQueryFilter_NonCanFilterQueryCondition_Exception() {

			$this->expectExceptionMessage('Only routes with a condition implementing the');

			$request = Mockery::mock( RequestInterface::class )->shouldIgnoreMissing();

			$this->subject->method( 'getAttribute' )
			              ->withConsecutive(
				              [ 'query' ],
				              [ 'condition' ]
			              )
			              ->will( $this->onConsecutiveCalls(
				              function () {
				              },
				              null
			              ) );

			$this->subject->applyQueryFilter( $request, [] );
		}

	}
