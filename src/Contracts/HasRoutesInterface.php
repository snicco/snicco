<?php


	declare( strict_types = 1 );
	/**
 * @package   WPEmerge
 * @author    Atanas Angelov <hi@atanas.dev>
 * @copyright 2017-2019 Atanas Angelov
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
 * @link      https://wpemerge.com/
 */

namespace WPEmerge\Contracts;

use WPEmerge\Contracts\RouteCondition;

/**
 * Interface for RegisteresRoutes
 */
interface HasRoutesInterface {
	/**
	 * Get routes.
	 *
	 * @return RouteCondition[]
	 */
	public function getRoutes();

	/**
	 * Add a route.
	 *
	 * @param  RouteCondition  $route
	 *
	 * @return void
	 */
	public function addRoute( RouteCondition $route );

	/**
	 * Remove a route.
	 *
	 * @param  RouteCondition $route
	 *
	 * @return void
	 */
	public function removeRoute( RouteCondition $route );
}
