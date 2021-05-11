<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;


	trait HasContainer {


		/** @var ContainerAdapter|null */
		private $container_adapter;


		public function container() : ?ContainerAdapter {

			return $this->container_adapter;

		}

		public function setContainerAdapter( ContainerAdapter $container_adapter ) :void {

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

			if ( ! isset( $this->container_adapter[ $key ] ) ) {
				return null;
			}

			return $this->container_adapter[ $key ];
		}



	}
