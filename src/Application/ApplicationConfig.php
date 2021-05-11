<?php

	declare(strict_types=1);

	namespace WPEmerge\Application;

	use Illuminate\Config\Repository;
	use WPEmerge\Support\Arr;

	class ApplicationConfig extends Repository {


		public function extend( string $key, $app_config ) : void {

			$user_config = $this->get( $key, [] );

			$value = $this->replaceConfig($app_config, $user_config);

			$this->set( $key, $value );

		}

		/**
		 * Recursively replace default values with the passed config.
		 * - If either value is not an array, the config value will be used.
		 * - If both are an indexed array, the config value will be used.
		 * - If either is a keyed array, array_replace will be used with config having priority.
		 *
		 * @param  mixed  $app_config
		 * @param  mixed  $user_config
		 *
		 * @return mixed
		 */
		private function replaceConfig( $app_config, $user_config ) {

			if ( $this->isEmptyArray($user_config) && ! $this->isEmptyArray($app_config) ) {

				return $app_config;

			}

			if ( ! is_array( $app_config ) ) {

				return $user_config;

			}

			$app_config_is_indexed  = array_keys( $app_config ) === range( 0, count( $app_config ) - 1 );
			$user_config_is_indexed = array_keys( $user_config ) === range( 0, count( $user_config ) - 1 );

			if ( $app_config_is_indexed && $user_config_is_indexed ) {

				return Arr::combineNumerical( $user_config, $app_config );

			}

			$result = $user_config;

			foreach ( $app_config as $key => $app_value ) {

				$result[ $key ] = $this->replaceConfig( $app_value, Arr::get( $user_config, $key, [] ) );

			}

			return $result;
		}

		private function isEmptyArray($value) :bool {

			return $value === [];

		}

	}