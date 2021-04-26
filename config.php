<?php



	/**
	 * Current version.
	 */
	if ( ! defined( 'WPEMERGE_VERSION' ) ) {
		define( 'WPEMERGE_VERSION', '0.16.0' );
	}

	/**
	 * Absolute path to application's directory.
	 */
	if ( ! defined( 'WPEMERGE_DIR' ) ) {
		define( 'WPEMERGE_DIR', __DIR__ );
	}

	/**
	 * Absolute path to application's directory.
	 */
	if ( ! defined( 'WPEMERGE_DUMMY_VIEW' ) ) {
		define( 'WPEMERGE_DUMMY_VIEW', __DIR__ . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . 'view.php' );
	}



	/**
	 *
	 * Using Class names for better compatibility container adapter.
	 *
	 * Service container keys.
	 */
	if ( ! defined( 'WPEMERGE_CONFIG_KEY' ) ) {
		define( 'WPEMERGE_CONFIG_KEY', 'wpemerge.config' );
	}

	if ( ! defined( 'WPEMERGE_APPLICATION_KEY' ) ) {
		define( 'WPEMERGE_APPLICATION_KEY', \WPEmerge\Application\Application::class );
	}

	if ( ! defined( 'WPEMERGE_CONTAINER_ADAPTER' ) ) {
		define( 'WPEMERGE_CONTAINER_ADAPTER', \Contracts\ContainerAdapter::class );
	}

	if ( ! defined( 'WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY' ) ) {
		define( 'WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY', \WPEmerge\Application\GenericFactory::class );
	}

	if ( ! defined( 'WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY' ) ) {
		define( 'WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY', \WPEmerge\Application\ClosureFactory::class );
	}

	if ( ! defined( 'WPEMERGE_HELPERS_HANDLER_FACTORY_KEY' ) ) {
		define( 'WPEMERGE_HELPERS_HANDLER_FACTORY_KEY', \WPEmerge\Helpers\HandlerFactory::class );
	}

	if ( ! defined( 'WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY' ) ) {
		define( 'WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY', \WPEmerge\Kernels\HttpKernel::class );
	}

	if ( ! defined( 'WPEMERGE_SESSION_KEY' ) ) {
		define( 'WPEMERGE_SESSION_KEY', 'wpemerge.session' );
	}

	if ( ! defined( 'WPEMERGE_REQUEST_KEY' ) ) {
		define( 'WPEMERGE_REQUEST_KEY', \WPEmerge\Contracts\RequestInterface::class );
	}

	if ( ! defined( 'WPEMERGE_RESPONSE_KEY' ) ) {
		define( 'WPEMERGE_RESPONSE_KEY', \Psr\Http\Message\ResponseInterface::class );
	}

	if ( ! defined( 'WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY' ) ) {
		define( 'WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY', \WPEmerge\Contracts\ErrorHandlerInterface::class );
	}

	if ( ! defined( 'WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY' ) ) {
		define( 'WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY', 'wpemerge.exceptions.configuration_error_handler' );
	}

	if ( ! defined( 'WPEMERGE_RESPONSE_SERVICE_KEY' ) ) {
		define( 'WPEMERGE_RESPONSE_SERVICE_KEY', \WPEmerge\Responses\ResponseService::class );
	}

	if ( ! defined( 'WPEMERGE_ROUTING_ROUTER_KEY' ) ) {
		define( 'WPEMERGE_ROUTING_ROUTER_KEY', \WPEmerge\Contracts\HasRoutesInterface::class );
	}

	if ( ! defined( 'WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY' ) ) {
		define( 'WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY', \WPEmerge\Routing\RouteBlueprint::class );
	}

	if ( ! defined( 'WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY' ) ) {
		define( 'WPEMERGE_ROUTING_CONDITIONS_CONDITION_FACTORY_KEY', \WPEmerge\Routing\Conditions\ConditionFactory::class );
	}

	if ( ! defined( 'WPEMERGE_ROUTING_CONDITION_TYPES_KEY' ) ) {
		define( 'WPEMERGE_ROUTING_CONDITION_TYPES_KEY', 'wpemerge.routing.conditions.condition_types' );
	}

	if ( ! defined( 'WPEMERGE_VIEW_SERVICE_KEY' ) ) {
		define( 'WPEMERGE_VIEW_SERVICE_KEY', \WPEmerge\Contracts\ViewServiceInterface::class);
	}

	if ( ! defined( 'WPEMERGE_VIEW_COMPOSE_ACTION_KEY' ) ) {
		define( 'WPEMERGE_VIEW_COMPOSE_ACTION_KEY', \WPEmerge\Contracts\ViewInterface::class );
	}

	if ( ! defined( 'WPEMERGE_VIEW_ENGINE_KEY' ) ) {
		define( 'WPEMERGE_VIEW_ENGINE_KEY', 'wpemerge.view.view_engine' );
	}

	if ( ! defined( 'WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY' ) ) {
		define( 'WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY', \WPEmerge\Contracts\ViewEngineInterface::class );
	}

	if ( ! defined( 'WPEMERGE_SERVICE_PROVIDERS_KEY' ) ) {
		define( 'WPEMERGE_SERVICE_PROVIDERS_KEY', 'wpemerge.service_providers' );
	}

	if ( ! defined( 'WPEMERGE_FLASH_KEY' ) ) {
		define( 'WPEMERGE_FLASH_KEY', \WPEmerge\Session\Flash::class );
	}

	if ( ! defined( 'WPEMERGE_OLD_INPUT_KEY' ) ) {
		define( 'WPEMERGE_OLD_INPUT_KEY', \WPEmerge\Input\OldInput::class );
	}

	if ( ! defined( 'WPEMERGE_CSRF_KEY' ) ) {
		define( 'WPEMERGE_CSRF_KEY', \WPEmerge\Csrf\Csrf::class );
	}


