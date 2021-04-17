<?php

	namespace WPEmerge\Application;

	use Pimple\Container;
	use Pimple\Exception\ExpectedInvokableException;

	class PimpleAdapter implements ContainerAdapterInterface {



		private Container $container;
		private string $name = 'pimple';

		public function __construct( Container $container ) {

			$this->container = $container;

		}

		/**
		 * Determine if a given offset exists.
		 *
		 * @param  string  $key
		 *
		 * @return bool
		 */
		public function offsetExists( $key ) {

			return $this->container->offsetExists( $key );
		}

		/**
		 * Get the value at a given offset.
		 *
		 * @param  string  $key
		 *
		 * @return mixed
		 * @throws \Illuminate\Contracts\Container\BindingResolutionException
		 */
		public function offsetGet( $key ) {

			return $this->container->offsetGet( $key );

		}


		/**
		 * Registers are shared binding in the container.
		 *
		 * @param  string  $key
		 * @param  mixed   $value
		 *
		 * @return void
		 */
		public function offsetSet( $key, $value ) {

			$this->container->offsetSet( $key, $value );

		}


		/**
		 * Unset the value at a given offset.
		 *
		 * @param  string  $key
		 *
		 * @return void
		 */
		public function offsetUnset( $key ) {

			$this->container->offsetUnset( $key );

		}


		/**
		 * Marks a callable as being a factory service.
		 *
		 * @param  callable  $callable  A service definition to be used as a factory
		 * @param  string    $key       A string alias to be used for the factory
		 *
		 * @return callable The passed callable
		 *
		 * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
		 */
		public function factory( $callable, $key = NULL ) {

			return $this->container->factory( $callable );

		}


		/**
		 * @param  string  $class
		 *
		 * @return object|null
		 */
		public function resolveClass( $class )  {


			if ( $this->offsetExists($class) && $class = $this->offsetGet($class)) {

				return $class;

			}

			return null;

		}

		/**
		 * Get the container instance managed by the adapter.
		 *
		 * @return \Pimple\Container
		 */
		public function getContainer() {

			return $this->container;

		}

		public function getName() {

			return $this->name;

		}

		/**
		 * @return \Closure
		 */
		public function getCallable() {

			return function (...$args)  {

				return call_user_func_array( $args[0], $args[1] );

			};

		}



	}
