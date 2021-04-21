<?php


	namespace WPEmerge\Middleware;

	use Contracts\ContainerAdapter;
	use WPEmerge\Helpers\ImplicitRouteBindings;
	use WPEmerge\Requests\Request;

	class SubstituteModelBindings {

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * SubstituteModelBindings constructor.
		 *
		 * @param  \Contracts\ContainerAdapter  $container
		 */
		public function __construct( ContainerAdapter $container ) {

			$this->container = $container;

		}

		public function handle ( Request $request, \Closure $next ) {

			// ImplicitRouteBindings::resolveForRoute($this->container, $request->route());

			return $next($request);

		}


	}