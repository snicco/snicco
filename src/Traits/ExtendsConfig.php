<?php



	namespace WPEmerge\Traits;

	use WPEmerge\Support\Arr;
	use WPEmerge\Support\WPEmgereArr;


	trait ExtendsConfig {


		/**
		 * Extends the WP Emerge config in the container with a new key.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return void
		 */
		public function extend( string $key, $default ) {


			// $config = isset( $container[ WPEMERGE_CONFIG_KEY ] ) ? $container[ WPEMERGE_CONFIG_KEY ] : [];
			// $config_part = WPEmgereArr::get( $config, $key, $default );
			//
			// $container[ WPEMERGE_CONFIG_KEY ] = array_merge(
			// 	$container[ WPEMERGE_CONFIG_KEY ],
			// 	[ $key => $this->replaceConfig( $default, $config_part ) ]
			// );
			//
			// $this->config = array_merge(
			// 	$this->config,
			// 	[$key => $this->replaceConfig($default, $config_part)]
			// );

		}



		/**
		 * Recursively replace default values with the passed config.
		 * - If either value is not an array, the config value will be used.
		 * - If both are an indexed array, the config value will be used.
		 * - If either is a keyed array, array_replace will be used with config having priority.
		 *
		 * @param  mixed  $default
		 * @param  mixed  $config
		 *
		 * @return mixed
		 */
		private function replaceConfig( $default, $config ) {

			if ( ! is_array( $default ) || ! is_array( $config ) ) {
				return $config;
			}

			$default_is_indexed = array_keys( $default ) === range( 0, count( $default ) - 1 );
			$config_is_indexed  = array_keys( $config ) === range( 0, count( $config ) - 1 );

			if ( $default_is_indexed && $config_is_indexed ) {
				return $config;
			}

			$result = $default;

			foreach ( $config as $key => $value ) {
				$result[ $key ] = $this->replaceConfig( WPEmgereArr::get( $default, $key ), $value );
			}

			return $result;
		}



	}
