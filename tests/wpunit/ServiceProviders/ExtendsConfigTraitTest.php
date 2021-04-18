<?php


	namespace Tests\wpunit\ServiceProviders;

	use Codeception\PHPUnit\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\ServiceProviders\ExtendsConfigTrait;

	/**
	 * @coversDefaultClass \WPEmerge\ServiceProviders\ExtendsConfigTrait
	 */
	class ExtendsConfigTraitTest extends TestCase {

		public function setUp() : void {

			parent::setUp();

			$this->subject = $this->getMockForTrait( ExtendsConfigTrait::class );
		}

		public function tearDown() : void {

			parent::tearDown();

			unset( $this->subject );
		}

		/**
		 * @covers ::extendConfig
		 * @covers ::replaceConfig
		 */
		public function testExtendConfig_ConfigNotSet_Default() {

			$container = new BaseContainerAdapter();

			$container[ WPEMERGE_CONFIG_KEY ] = [];

			$key      = 'foo';
			$default  = 'bar';
			$expected = $default;

			$this->subject->extendConfig( $container, $key, $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ $key ] );
		}

		/**
		 * @covers ::extendConfig
		 * @covers ::replaceConfig
		 */
		public function testExtendConfig_NotArrays_Replace() {

			$container = new BaseContainerAdapter();

			$container[ WPEMERGE_CONFIG_KEY ] = [
				'foo' => 'foo',
			];

			$key      = 'foo';
			$default  = 'bar';
			$expected = 'foo';

			$this->subject->extendConfig( $container, $key, $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ $key ] );
		}

		/**
		 * @covers ::extendConfig
		 * @covers ::replaceConfig
		 */
		public function testExtendConfig_Arrays_RecursiveReplace() {

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
				// Value is NOT replaced by default.
				'bar'       => 'bar',
				'baz'       => [
					'foo' => 'foo',
					// Key from default is added in nested array.
					'bar' => 'bar',
				],
				// Key from default is added.
				'foobarbaz' => 'foobarbaz',
			];

			$this->subject->extendConfig( $container, $key, $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ $key ] );
		}

		/**
		 * @covers ::extendConfig
		 * @covers ::replaceConfig
		 */
		public function testExtendConfig_IndexedArray_Replace() {

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

			$key      = 'first';
			$default  = [
				'foo',
				'foo',
			];
			$expected = [
				'bar',
			];

			$this->subject->extendConfig( $container, $key, $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ $key ] );

			$key      = 'second';
			$default  = [
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

			$this->subject->extendConfig( $container, $key, $default );

			$this->assertEquals( $expected, $container[ WPEMERGE_CONFIG_KEY ][ $key ] );
		}

	}
