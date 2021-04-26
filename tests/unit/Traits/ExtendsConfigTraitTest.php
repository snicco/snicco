<?php


	namespace Tests\unit\Traits;

	use Codeception\PHPUnit\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Traits\ExtendsConfig;


	class ExtendsConfigTraitTest extends TestCase {


		/**
		 * @var \Tests\unit\Traits\TestServiceProvider
		 */
		private $subject;

		public function setUp() : void {

			parent::setUp();

			$this->subject = new TestServiceProvider();
		}

		public function tearDown() : void {
			parent::tearDown();
			unset( $this->subject );

		}

		/** @test */
		public function the_default_gets_set_if_the_key_is_not_present_in_the_user_config() {

			$container = new BaseContainerAdapter();
			$container[ WPEMERGE_CONFIG_KEY ] = [];


			$this->subject->extendConfig( $container, 'foo', 'bar' );

			$this->assertEquals( 'bar', $container[ WPEMERGE_CONFIG_KEY ][ 'foo' ] );


		}

		/** @test */
		public function user_config_has_precedence_over_default_config() {

			$container = new BaseContainerAdapter();

			$container[ WPEMERGE_CONFIG_KEY ] = [
				'foo' => 'foo',
			];


			$this->subject->extendConfig( $container, 'foo', 'bar' );

			$this->assertEquals( 'foo', $container[ WPEMERGE_CONFIG_KEY ][ 'foo' ] );
		}

		/** @test */
		public function user_config_has_precedence_over_default_config_and_gets_merged_recursively() {

			$container = new BaseContainerAdapter();

			$container[ WPEMERGE_CONFIG_KEY ] = [
				'foo' => [
					'foo' => 'foo',
					'bar' => 'bar',
					'baz' => [
						'foo' => 'foo',
					],
				],
			];

			$key      = 'foo';
			$default  = [
				'bar'       => 'foobarbaz',
				'baz'       => [
					'bar' => 'bar',
				],
				'foobarbaz' => 'foobarbaz',
			];
			$expected = [
				// Value is NOT missing.
				'foo'       => 'foo',
				// Value is NOT replaced by default value.
				'bar'       => 'bar',
				'baz'       => [
					'foo' => 'foo',
					// Key from default is added in nested array.
					'bar' => 'bar',
				],
				// Key from default is added.
				'foobarbaz' => 'foobarbaz',
			];

			$this->subject->extendConfig( $container, 'foo', $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ 'foo' ] );
		}

		/** @test */
		public function numerically_indexed_arrays_get_replaced() {

			$container = new BaseContainerAdapter();

			$container[ WPEMERGE_CONFIG_KEY ] = [
				'first'  => [
					'bar',
				],
				'second' => [
					'foobar' => [
						'barfoo',
						'barfoo',
					],
				],
				'third'  => [
				],
			];

			$default_1  = [
				'foo',
				'foo',
			];


			$this->subject->extendConfig( $container, 'first', $default_1 );

			$this->assertEquals( ['bar'], $container[ WPEMERGE_CONFIG_KEY ][ 'first' ] );

			$default_2  = [
				'foobar' => [
					'foobar',
				],
			];
			$expected = [
				'foobar' => [
					'barfoo',
					'barfoo',
				],
			];

			$this->subject->extendConfig( $container, 'second', $default_2 );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ 'second' ] );
		}


	}

	class TestServiceProvider {

		use ExtendsConfig;

	}
