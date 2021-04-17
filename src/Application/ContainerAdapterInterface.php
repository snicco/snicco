<?php

	namespace WPEmerge\Application;

	use WPEmerge\Exceptions\ClassNotFoundException;

	interface ContainerAdapterInterface extends \ArrayAccess {


		/**
		 * Marks a callable as being a factory service.
		 *
		 * @param  callable  $value  A service definition to be used as a factory
		 * @param  string    $key    A string alias to be used for the factory
		 *
		 * @return callable The passed callable
		 */
		public function factory( $value, $key = NULL );

		/**
		 * Try to resolve a class from the service container
		 *
		 * @param  string  $class
		 *
		 * @return object|null
		 */
		public function resolveClass( $class );


		/**
		 * Get the container instance managed by the adapter.
		 *
		 * @return object
		 */
		public function getContainer();


		/**
		 * Get the container instance name
		 *
		 * @return string
		 */
		public function getName();


		public function getCallable();

	}
