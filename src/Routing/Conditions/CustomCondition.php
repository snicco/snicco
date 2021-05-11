<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;


	class CustomCondition implements ConditionInterface {


		/** @var callable  */
		private $callable;

		private $arguments;


		public function __construct( callable $callable, ...$args  ) {

			$this->callable  = $callable;
			$this->arguments = $args;

		}


		public function isSatisfied( RequestInterface $request ) {

			return call_user_func_array( $this->callable, $this->arguments );

		}


		public function getArguments( RequestInterface $request ) : array {

			return $this->arguments;

		}

	}
