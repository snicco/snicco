<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Helpers\HasAttributes;
	use WPEmerge\Traits\HoldsRouteBlueprint;

	/**
	 *
	 * Fluent Interface for registering routes
	 * in the router.
	 *
	 */
	class RouteBlueprint {

		use HasAttributes;
		use HoldsRouteBlueprint;

		/** @var \WPEmerge\Routing\Router */
		private $router;

		/** @var ViewServiceInterface */
		protected $view_service;


		public function __construct( Router $router, ViewServiceInterface $view_service ) {

			$this->router       = $router;
			$this->view_service = $view_service;

		}


	}
