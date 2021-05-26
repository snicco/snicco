<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use WPEmerge\Http\Psr7\Request;


	interface ConditionInterface {

		/**
		 * Get whether the condition is satisfied
		 *
		 * @param  Request  $request
		 *
		 * @return boolean
		 */
		public function isSatisfied( Request $request ) :bool;

		/**
		 * Get an array of arguments for use in request
		 *
		 * @param  Request  $request
		 *
		 * @return array
		 */
		public function getArguments( Request $request ) :array;

	}
