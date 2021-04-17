<?php
	/**
	 * @package   WPEmerge
	 * @author    Atanas Angelov <hi@atanas.dev>
	 * @copyright 2017-2019 Atanas Angelov
	 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
	 * @link      https://wpemerge.com/
	 */


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
		protected $container_adapter = null;

		/**
		 * Get the IoC container instance.
		 *
		 * @codeCoverageIgnore
		 * @return ContainerAdapter
		 */
		public function container() : ContainerAdapter {

			return $this->container_adapter;

		}

		/**
		 * Set the IoC container instance.
		 *
		 * @codeCoverageIgnore
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
		public function resolve( $key ) {

			if ( ! isset( $this->container()[ $key ] ) ) {
				return null;
			}

			return $this->container()[ $key ];
		}

		/**
		 *
		 * Swaps a dependency in the IoC container
		 *
		 */
		public function swapInstance( $key, $new_instance ) {

			if ( ! isset( $this->container()[ $key ] ) ) {
				return;
			}

			$this->container()[ $key ] = $new_instance;

		}

	}
