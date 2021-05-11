<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Session\Csrf;
	use WPEmerge\Session\FlashStore;
	use WPEmerge\Session\OldInputStore;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Http\RedirectResponse;
	use WPEmerge\Http\ResponseService;
	use WPEmerge\Routing\RouteBlueprint;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\ViewService;

	/**
	 * Can be applied to your App class via a "@mixin" annotation for better IDE support.
	 * This class is not meant to be used in any other capacity.
	 *
	 * @codeCoverageIgnore
	 */
	final class ApplicationMixin {

		/**
		 * Prevent class instantiation.
		 */
		private function __construct() {
		}

		// --- Methods --------------------------------------- //

		/**
		 *
		 * Resolve an item from the applications config.
		 *
		 * @param  string  $key
		 * @param $default
		 * @return mixed
		 */
		public static function config (string $key, $default = null ) {}

		/**
		 * Bootstrap the application.
		 *
		 * @param  array  $config
		 * @param  boolean  $run
		 *
		 * @return void
		 */
		public static function bootstrap( $config = [], $run = true ) {
		}

		/**
		 * Get the IoC container instance.
		 *
		 * @return \Contracts\ContainerAdapter
		 */
		public static function container() : ContainerAdapter {
		}

		/**
		 * Set the IoC container instance.
		 *
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		public static function setContainer( ContainerAdapter $container ) {
		}

		/**
		 * Resolve a dependency from the IoC container.
		 *
		 * @param  string  $key
		 *
		 * @return mixed|null
		 */
		public static function resolve( $key ) {
		}

		// --- Aliases --------------------------------------- //

		/**
		 * Get the Application instance.
		 *
		 * @return \WPEmerge\Application\Application
		 */
		public static function app() : Application {
		}

		/**
		 * Get the ClosureFactory instance.
		 *
		 * @return ClosureFactory
		 */
		public static function closure() : ClosureFactory {
		}

		/**
		 * Get the CSRF service instance.
		 *
		 * @return \WPEmerge\Session\Csrf
		 */
		public static function csrf() : Csrf {
		}

		/**
		 * Get the FlashStore service instance.
		 *
		 * @return \WPEmerge\Session\FlashStore
		 */
		public static function flash() : FlashStore {
		}

		/**
		 * Get the OldInputStore service instance.
		 *
		 * @return \WPEmerge\Session\OldInputStore
		 */
		public static function oldInput() : OldInputStore {
		}

		/**
		 * Run a full middleware + handler pipeline independently of routes.
		 *
		 * @param  RequestInterface  $request
		 * @param  string[]  $middleware
		 * @param  string|\Closure  $handler
		 * @param  array  $arguments
		 *
		 * @return ResponseInterface
		 * @see    \WPEmerge\Http\HttpKernel::run()
		 */
		public static function run( RequestInterface $request, $middleware, $handler, $arguments = [] ) : ResponseInterface {
		}

		/**
		 * Get the ResponseService instance.
		 *
		 * @return ResponseService
		 */
		public static function responses() : ResponseService {
		}

		/**
		 * Create a "blank" response.
		 *
		 * @return ResponseInterface
		 * @see    \WPEmerge\Http\ResponseService::response()
		 */
		public static function response() : ResponseInterface {
		}

		/**
		 * Create a response with the specified string as its body.
		 *
		 * @param  string  $output
		 *
		 * @return ResponseInterface
		 * @see    \WPEmerge\Http\ResponseService::output()
		 */
		public static function output( $output ) : ResponseInterface {
		}

		/**
		 * Create a response with the specified data encoded as JSON as its body.
		 *
		 * @param  mixed  $data
		 *
		 * @return \WPEmerge\Http\ResponseService
		 * @see    \WPEmerge\Http\ResponseService::json()
		 */
		public static function json( $data ) : ResponseService {
		}

		/**
		 * Create a redirect response.
		 *
		 * @return RedirectResponse
		 * @see    \WPEmerge\Http\ResponseService::redirect()
		 */
		public static function redirect() : RedirectResponse {
		}

		/**
		 * Create a response with the specified error status code.
		 *
		 * @param  integer  $status
		 *
		 * @return ResponseInterface
		 * @see    \WPEmerge\Http\ResponseService::abort()
		 */
		public static function error( $status ) : ResponseInterface {
		}

		/**
		 * Get the ViewService instance.
		 *
		 * @return \WPEmerge\View\ViewService
		 */
		public static function views() : ViewService {
		}

		/**
		 * Create a view
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
		 * @see    \WPEmerge\View\ViewService::make()
		 */
		public static function view( $views ) : ViewInterface {
		}

		/**
		 * Output child layout content.
		 *
		 * @return void
		 * @see    \WPEmerge\View\PhpViewEngine::getLayoutContent()
		 */
		public static function layoutContent() {
		}

		/**
		 * Create a new route.
		 *
		 * @return \WPEmerge\Routing\Router
		 */
		public static function route() : Router {
		}

		/**
		 * Output the specified view.
		 *
		 * @param  string|string[]  $views
		 * @param  array<string, mixed>  $context
		 *
		 * @return void
		 * @see    \WPEmerge\Contracts\ViewInterface::toString()
		 * @see    \WPEmerge\View\ViewService::make()
		 */
		public static function render( $views, $context = [] ) {
		}

		/**
		 * @param string|string[] $views
		 * @param string|array|callable|\Closure $callable
		 */
		public static function addComposer($views, $callable) {}

		public static function globals() : VariableBag {}

	}
