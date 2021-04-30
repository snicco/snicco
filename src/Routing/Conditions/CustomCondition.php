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
		private $callable;

		/**
		 * Arguments to pass to the callable and controller
		 *
		 * @var array
		 */
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
