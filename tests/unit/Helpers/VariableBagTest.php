<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Helpers;

	use WPEmerge\Support\VariableBag;
	use PHPUnit\Framework\TestCase;

	class VariableBagTest extends TestCase {


		/** @test */
		public function the_prefix_can_be_set() {

			$var_bag = new VariableBag();

			$var_bag->setPrefix( 'foo' );

			$this->assertSame( 'foo', $var_bag->getPrefix() );


		}

		/** @test */
		public function single_elements_can_be_added() {

			$var_bag = new VariableBag();

			$var_bag->add( [ 'foo' => 'bar' ] );

			$this->assertSame( 'bar', $var_bag['foo'] );

		}

		/** @test */
		public function multiple_elements_can_be_added() {

			$var_bag = new VariableBag();

			$var_bag->add( [ 'foo' => 'bar', 'bar' => 'baz' ] );

			$this->assertSame( 'bar', $var_bag['foo'] );
			$this->assertSame( 'baz', $var_bag['bar'] );

		}

		/** @test */
		public function nested_elements_are_accessible_as_dot_notation() {

			$var_bag = new VariableBag();

			$var_bag->add( [

				'foo' => [

					'bar' => [

						'baz' => 'biz'

					]

				]

				] );

			$this->assertSame( 'biz', $var_bag['foo.bar.baz'] );

		}

	}
