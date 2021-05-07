<?php


	namespace Tests\unit\Traits;

	use Codeception\PHPUnit\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Traits\ExtendsConfig;


	class ExtendsConfigTest extends TestCase {


		/**
		 * @var \WPEmerge\Application\ApplicationConfig
		 */
		private $config;

		public function setUp() : void {

			parent::setUp();

			$this->config = new ApplicationConfig();

		}


		/** @test */
		public function the_default_gets_set_if_the_key_is_not_present_in_the_user_config() {

			$this->assertSame(null , $this->config->get('foo'));

			$this->config->extend('foo', 'bar');

			$this->assertEquals( 'bar', $this->config->get('foo') );


		}

		/** @test */
		public function user_config_has_precedence_over_default_config() {

			$this->assertSame(null , $this->config->get('foo'));

			$this->config->set('foo', 'bar');

			$this->assertSame('bar' , $this->config->get('foo'));

			$this->config->extend('foo', 'baz');

			$this->assertSame('bar' , $this->config->get('foo'));

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

	class TestServiceProvider extends ServiceProvider {

		use ExtendsConfig;

		public function register() : void {
		}

		function bootstrap() : void {
		}

	}
