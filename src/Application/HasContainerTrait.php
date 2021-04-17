<?php


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;

	/**
	 * Holds an IoC container.
	 */
	trait HasContainerTrait {

		/**
		 * IoC container.
		 *
		 * @var ContainerAdapter
		 */
		private $container_adapter = null;

		/**
		 * Get the IoC container instance.
		 *
		 * @return ContainerAdapter
		 */
		public function container() : ContainerAdapter {

			return $this->container_adapter;

		}

		/**
		 * Set the IoC container instance.
		 *
		 *
		 * @param  ContainerAdapter  $container_adapter
		 *
		 * @return void
		 */
		public function setContainerAdapter( ContainerAdapter $container_adapter ) {

			$this->container_adapter = $container_adapter;

		}

		/**
		 * Resolve a dependency from the IoC container.
		 *
		 * @param  string  $key
		 *
		 * @return mixed|null
		 */
		public function resolve( string $key ) {

			if ( ! isset( $this->container()[ $key ] ) ) {
				return null;
			}

			return $this->container()[ $key ];
		}



	}
