<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

	use Tests\fixtures\TestDependencies\Foo;
	use BetterWP\Contracts\ConditionInterface;
    use BetterWP\Http\Psr7\Request;

    class ConditionWithDependency implements ConditionInterface {


		/**
		 * @var bool
		 */
		private $make_it_pass;

		/**
		 * @var Foo
		 */
		private $foo;

		public function __construct( $make_it_pass, Foo $foo ) {

			$this->make_it_pass = $make_it_pass;
			$this->foo          = $foo;

		}

		public function isSatisfied( Request $request ) : bool {

			if ( ! isset( $this->foo ) ) {

				return false;

			}

			return $this->make_it_pass === true || $this->make_it_pass === 'foobar';
		}

		public function getArguments( Request $request ) :array {

			return [];

		}

	}