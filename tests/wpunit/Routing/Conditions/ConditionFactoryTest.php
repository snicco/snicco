<?php

namespace Tests\wpunit\Routing\Conditions;

use Codeception\TestCase\WPTestCase;
use Mockery as m;
use SniccoAdapter\BaseContainerAdapter;
use stdClass;
use WPEmerge\Application\Application;
use WPEmerge\Contracts\RequestInterface;
use WPEmerge\Routing\ConditionFactory;
use WPEmerge\Contracts\ConditionInterface;
use WPEmerge\Routing\Conditions\CustomCondition;
use WPEmerge\Routing\Conditions\MultipleCondition;
use WPEmerge\Routing\Conditions\NegateCondition;
use WPEmerge\Routing\Conditions\PostIdCondition;
use WPEmerge\Routing\Conditions\UrlCondition;

/**
 * @coversDefaultClass \WPEmerge\Routing\ConditionFactory
 */
class ConditionFactoryTest extends WPTestCase {
	public $request;

	public $subject;

	public function setUp() :void  {
		parent::setUp();

		$app = new Application( new BaseContainerAdapter(), false );
		$app->bootstrap( [], false );
		$condition_types = $app->resolve( WPEMERGE_ROUTING_CONDITION_TYPES_KEY );

		$this->request = m::mock( RequestInterface::class );
		$this->subject = new ConditionFactory( $condition_types, m::mock(BaseContainerAdapter::class));
	}

	public function tearDown() :void  {
		parent::tearDown();
		m::close();

		unset( $this->request );
		unset( $this->subject );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromUrl
	 */
	public function testMake_Url_UrlCondition() {
		$expected_param = '/foo/bar/';
		$expected_class = UrlCondition::class;

		$condition = $this->subject->makeNew( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( $expected_param, $condition->getUrl() );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_ConditionInArray_ConditionInstance() {
		$expected_param = 10;
		$expected_class = PostIdCondition::class;

		$condition = $this->subject->makeNew( ['post_id', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( $expected_param, $condition->getArguments( $this->request )['post_id'] );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CustomConditionWithClosureInArray_CustonCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->makeNew( ['custom', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CustomConditionWithCallableInArray_CustomCondition() {
		$expected_param = 'phpinfo';
		$expected_class = CustomCondition::class;

		$condition = $this->subject->makeNew( ['custom', $expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 * @covers ::getConditionTypeClass
	 */
	public function testMake_ClosureInArray_CustomCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->makeNew( [$expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 */
	public function testMake_CallableInArray_CustomCondition() {
		$expected_param = 'phpinfo';
		$expected_class = CustomCondition::class;

		$condition = $this->subject->makeNew( [$expected_param] );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::makeFromArrayOfConditions
	 */
	public function testMake_ArrayOfConditionsInArray_MultipleCondition() {
		$expected_param1 = function() {};
		$expected_param2 = m::mock( PostIdCondition::class );
		$expected_class = MultipleCondition::class;

		$condition = $this->subject->makeNew( [ [ $expected_param1 ], $expected_param2 ] );
		$this->assertInstanceOf( $expected_class, $condition );

		$condition_conditions = $condition->getConditions();
		$this->assertSame( $expected_param1, $condition_conditions[0]->getCallable() );
		$this->assertSame( $expected_param2, $condition_conditions[1] );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::isNegatedCondition
	 * @covers ::parseNegatedCondition
	 */
	public function testMake_ExclamatedConditionName_NegateCondition() {
		$expected_class = NegateCondition::class;

		$condition = $this->subject->makeNew( ['!query_var', 'foo', 'bar'] );
		$this->assertInstanceOf( $expected_class, $condition );

		$this->assertEquals( ['query_var' => 'foo', 'value' => 'bar'], $condition->getArguments( $this->request ) );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 * @covers ::parseConditionOptions
	 * @covers ::conditionTypeRegistered
	 * @covers ::getConditionTypeClass
	 */
	public function testMake_UnknownConditionType_Exception() {

		$this->expectExceptionMessage('Unknown condition');

		$expected_param = 'foobar';
		$this->subject->makeNew( [ $expected_param ] );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 */
	public function testMake_NoConditionType_Exception() {

		$this->expectExceptionMessage('No condition type');

		$this->subject->makeNew( [] );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromArray
	 */
	public function testMake_NonexistentConditionType_Exception() {

		$this->expectExceptionMessage('Error while creating the RouteCondition');

		$subject = new ConditionFactory( ['nonexistent_condition_type' => 'Nonexistent\\Condition\\Type\\Class'], m::mock(BaseContainerAdapter::class) );
		$subject->makeNew( ['nonexistent_condition_type'] );
	}

	/**
	 * @covers ::makeNew
	 * @covers ::makeFromClosure
	 */
	public function testMake_Closure_CustomCondition() {
		$expected_param = function() {};
		$expected_class = CustomCondition::class;

		$condition = $this->subject->makeNew( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertSame( $expected_param, $condition->getCallable() );
	}

	/**
	 * @covers ::makeNew
	 */
	public function testMake_Callable_UrlCondition() {
		$expected_param = 'phpinfo';
		$expected_class = UrlCondition::class;

		$condition = $this->subject->makeNew( $expected_param );
		$this->assertInstanceOf( $expected_class, $condition );
		$this->assertEquals( '/' . $expected_param . '/', $condition->getUrl() );
	}

	/**
	 * @covers ::makeNew
	 * @expectedException \WPEmerge\Exceptions\ConfigurationException
	 * @expectedExceptionMessage Invalid condition options
	 */
	public function testMake_Object_Exception() {

		$this->expectExceptionMessage('Invalid condition options');


		$this->subject->makeNew( new stdClass() );
	}

	/**
	 * @covers ::condition
	 */
	public function testCondition() {
		$condition = m::mock( ConditionInterface::class );
		$subject = m::mock( ConditionFactory::class )->makePartial();

		$this->assertSame( $condition, $subject->condition( $condition ) );

		$subject->shouldReceive( 'make' )
			->with( '' )
			->once();

		$subject->condition( '' );
		$this->assertTrue( true );
	}

	/**
	 * @covers ::merge
	 */
	public function testMerge() {
		$condition1 = m::mock( ConditionInterface::class );
		$condition2 = m::mock( ConditionInterface::class );

		$this->assertNull( $this->subject->merge( '', '' ) );
		$this->assertSame( $condition1, $this->subject->merge( $condition1, '' ) );
		$this->assertSame( $condition1, $this->subject->merge( '', $condition1 ) );
		$this->assertInstanceOf( MultipleCondition::class, $this->subject->merge( $condition1, $condition2 ) );
	}

	/**
	 * @covers ::mergeConditions
	 */
	public function testMergeConditions() {
		$this->assertInstanceOf( MultipleCondition::class, $this->subject->mergeConditions(
			m::mock( ConditionInterface::class ),
			m::mock( ConditionInterface::class )
		) );

		$url1 = m::mock( UrlCondition::class );
		$url2 = m::mock( UrlCondition::class )->shouldIgnoreMissing();
		$expected = m::mock( ConditionInterface::class );

		$url1->shouldReceive( 'concatenate' )
			->andReturn( $expected );

		$this->assertSame( $expected, $this->subject->mergeConditions( $url1, $url2 ) );
	}
}
