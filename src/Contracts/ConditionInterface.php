<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	/**
	 * Interface that condition types must implement
	 */
	interface ConditionInterface {

		/**
		 * Get whether the condition is satisfied
		 *
		 * @param  RequestInterface  $request
		 *
		 * @return boolean
		 */
		public function isSatisfied( RequestInterface $request );

		/**
		 * Get an array of arguments for use in request
		 *
		 * @param  RequestInterface  $request
		 *
		 * @return array
		 */
		public function getArguments( RequestInterface $request );

	}
