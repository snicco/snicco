<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	/**
	 * Check against a custom callable.
	 */
	class CustomCondition implements ConditionInterface {

		/**
		 * Callable to use
		 *
		 * @var callable
		 */
		protected $callable = null;

		/**
		 * Arguments to pass to the callable and controller
		 *
		 * @var array
		 */
		protected $arguments = [];


		public function __construct( callable $callable ) {

			$this->callable  = $callable;
			$this->arguments = array_values( array_slice( func_get_args(), 1 ) );
		}


		public function getCallable() {

			return $this->callable;
		}


		public function isSatisfied( RequestInterface $request ) {

			return call_user_func_array( $this->callable, $this->arguments );
		}


		public function getArguments( RequestInterface $request ) {

			return $this->arguments;
		}

	}
