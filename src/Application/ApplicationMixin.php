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
use Psr\Http\Message\ResponseInterface;
use WPEmerge\Csrf\Csrf;
use WPEmerge\Flash\Flash;
use WPEmerge\Input\OldInput;
use WPEmerge\Requests\RequestInterface;
use WPEmerge\Responses\RedirectResponse;
use WPEmerge\Responses\ResponseService;
use WPEmerge\Routing\RouteBlueprint;
use WPEmerge\View\ViewInterface;
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
	private function __construct() {}

	// --- Methods --------------------------------------- //

	/**
	 * Get whether the application has been bootstrapped.
	 *
	 * @return boolean
	 */
	public static function isBootstrapped() {}

	/**
	 * Bootstrap the application.
	 *
	 * @param  array   $config
	 * @param  boolean $run
	 * @return void
	 */
	public static function bootstrap( $config = [], $run = true ) {}

	/**
	 * Get the IoC container instance.
	 *
	 * @return \Contracts\ContainerAdapter
	 */
	public static function container() : ContainerAdapter {}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  ContainerAdapter $container
	 * @return void
	 */
	public static function setContainer( ContainerAdapter $container ) {}

	/**
	 * Resolve a dependency from the IoC container.
	 *
	 * @param  string     $key
	 * @return mixed|null
	 */
	public static function resolve( $key ) {}

	// --- Aliases --------------------------------------- //

	/**
	 * Get the Application instance.
	 *
	 * @return \WPEmerge\Application\Application
	 */
	public static function app() :Application {}

	/**
	 * Get the ClosureFactory instance.
	 *
	 * @return ClosureFactory
	 */
	public static function closure() :ClosureFactory {}

	/**
	 * Get the CSRF service instance.
	 *
	 * @return \WPEmerge\Csrf\Csrf
	 */
	public static function csrf() :Csrf {}

	/**
	 * Get the Flash service instance.
	 *
	 * @return \WPEmerge\Flash\Flash
	 */
	public static function flash() :Flash {}

	/**
	 * Get the OldInput service instance.
	 *
	 * @return \WPEmerge\Input\OldInput
	 */
	public static function oldInput() :OldInput {}

	/**
	 * Run a full middleware + handler pipeline independently of routes.
	 *
	 * @see    \WPEmerge\Kernels\HttpKernel::run()
	 * @param  RequestInterface  $request
	 * @param  string[]          $middleware
	 * @param  string|\Closure   $handler
	 * @param  array             $arguments
	 * @return ResponseInterface
	 */
	public static function run( RequestInterface $request, $middleware, $handler, $arguments = [] ) : ResponseInterface {}

	/**
	 * Get the ResponseService instance.
	 *
	 * @return ResponseService
	 */
	public static function responses() : ResponseService {}

	/**
	 * Create a "blank" response.
	 *
	 * @see    \WPEmerge\Responses\ResponseService::response()
	 * @return ResponseInterface
	 */
	public static function response() :ResponseInterface {}

	/**
	 * Create a response with the specified string as its body.
	 *
	 * @see    \WPEmerge\Responses\ResponseService::output()
	 * @param  string            $output
	 * @return ResponseInterface
	 */
	public static function output( $output ) : ResponseInterface {}

	/**
	 * Create a response with the specified data encoded as JSON as its body.
	 *
	 * @param  mixed  $data
	 *
	 * @return \WPEmerge\Responses\ResponseService
	 * @see    \WPEmerge\Responses\ResponseService::json()
	 */
	public static function json( $data ) :ResponseService {}

	/**
	 * Create a redirect response.
	 *
	 * @see    \WPEmerge\Responses\ResponseService::redirect()
	 * @return RedirectResponse
	 */
	public static function redirect() :RedirectResponse {}

	/**
	 * Create a response with the specified error status code.
	 *
	 * @see    \WPEmerge\Responses\ResponseService::error()
	 * @param  integer           $status
	 * @return ResponseInterface
	 */
	public static function error( $status ) : ResponseInterface {}

	/**
	 * Get the ViewService instance.
	 *
	 * @return \WPEmerge\View\ViewService
	 */
	public static function views() : ViewService{}

	/**
	 * Create a view.
	 *
	 * @see    \WPEmerge\View\ViewService::make()
	 * @param  string|string[] $views
	 * @return ViewInterface
	 */
	public static function view( $views ) :ViewInterface {}

	/**
	 * Output child layout content.
	 *
	 * @see    \WPEmerge\View\PhpViewEngine::getLayoutContent()
	 * @return void
	 */
	public static function layoutContent() {}

	/**
	 * Create a new route.
	 *
	 * @return RouteBlueprint
	 */
	public static function route() :RouteBlueprint {}

	/**
	 * Output the specified view.
	 *
	 * @see    \WPEmerge\View\ViewService::make()
	 * @see    \WPEmerge\View\ViewInterface::toString()
	 * @param  string|string[]      $views
	 * @param  array<string, mixed> $context
	 * @return void
	 */
	public static function render( $views, $context = [] ) {}
}
