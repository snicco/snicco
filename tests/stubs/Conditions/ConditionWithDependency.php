<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Conditions;

	use Tests\stubs\Foo;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	class ConditionWithDependency implements ConditionInterface {


		/**
		 * @var bool
		 */
		private $make_it_pass;

		/**
		 * @var \Tests\stubs\Foo
		 */
		private $foo;

		public function __construct( $make_it_pass, Foo $foo ) {

			$this->make_it_pass = $make_it_pass;
			$this->foo          = $foo;

		}

		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! isset( $this->foo ) ) {

				return false;

			}

			return $this->make_it_pass === true || $this->make_it_pass === 'foobar';
		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}