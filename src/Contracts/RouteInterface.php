<?php


	namespace WPEmerge\Contracts;



	/**
	 * Interface that routes must implement
	 */
	interface RouteInterface extends HasAttributesInterface {

		/**
		 * Get whether the route is satisfied.
		 *
		 * @param  RequestInterface  $request
		 *
		 * @return boolean
		 */
		public function isSatisfied( RequestInterface $request );

		/**
		 * Get arguments.
		 *
		 * @param  RequestInterface  $request
		 *
		 * @return array
		 */
		public function getArguments( RequestInterface $request );

		public function arguments() : array;

		public function setArguments(RequestInterface $request);

		public function updateArguments(array $arguments);

		public function signatureParameters();
	}
