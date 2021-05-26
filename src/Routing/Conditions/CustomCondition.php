<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Http\Psr7\Request;

    class CustomCondition implements ConditionInterface {


		/** @var callable  */
		private $callable;

		private $arguments;


		public function __construct( callable $callable, ...$args  ) {

			$this->callable  = $callable;
			$this->arguments = $args;

		}


		public function isSatisfied( Request $request ) :bool {

			return call_user_func_array( $this->callable, $this->arguments );

		}


		public function getArguments( Request $request ) : array {

			return $this->arguments;

		}

	}
