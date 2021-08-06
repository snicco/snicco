<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;
    use Tests\fixtures\TestDependencies\Foo;

    class ConditionWithDependency implements ConditionInterface {

		private bool $make_it_pass;
		private Foo $foo;

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