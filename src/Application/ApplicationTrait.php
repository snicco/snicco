<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use BadMethodCallException;
	use WPEmerge\Exceptions\ConfigurationException;

	/**
	 * Provides static access to an Application instance.
	 *
	 * @mixin ApplicationMixin
	 */
	trait ApplicationTrait {

		/**
		 * Application instance.
		 *
		 * @var Application|null
		 */
		public static $instance = NULL;

		/**
		 * Make and assign a new application instance.
		 *
		 * @param  string|\Contracts\ContainerAdapter  $containerAdapter  ::class or default
		 *
		 * @return Application
		 */
		public static function make( $containerAdapter = 'default' ) {

			static::setApplication( Application::create( $containerAdapter ) );

			return static::getApplication();
		}

		/**
		 * Get the Application instance.
		 *
		 * @return Application|null
		 */
		public static function getApplication() : ?Application {
			return static::$instance;
		}

		/**
		 * Set the Application instance.
		 *
		 *
		 * @param  Application|null  $application
		 *
		 * @return void
		 */
		public static function setApplication( ?Application $application ) {
			static::$instance = $application;
		}

		/**
		 * Invoke any matching instance method for the static method being called.
		 *
		 * @param  string  $method
		 * @param  array  $parameters
		 *
		 * @return mixed
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public static function __callStatic( string $method, array $parameters ) {


			$application = static::getApplication();
			$callable    = [ $application, $method ];

			if ( ! $application ) {
				throw new ConfigurationException(
					'Application instance not created in ' . static::class . '. ' .
					'Did you miss to call ' . static::class . '::make()?'
				);
			}

			if ( ! is_callable( $callable ) ) {
				throw new BadMethodCallException( 'Method ' . get_class( $application ) . '::' . $method . '() does not exist.' );
			}

			return call_user_func_array( $callable, $parameters );
		}

	}
