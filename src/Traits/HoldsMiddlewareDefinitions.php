<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;


	trait HoldsMiddlewareDefinitions {

		/**
		 *
		 * The application's route middleware groups.
		 *
		 * @var array
		 *
		 */
		private $middleware_groups = [];

		/**
		 *
		 * These middleware may be assigned to groups or used individually
		 *
		 * @var array
		 *
		 */
		private $route_middleware_aliases = [];

		/**
		 * The priority-sorted list of middleware.
		 *
		 * Forces non-global middleware to always be in the given order.
		 *
		 * @var string[]
		 */
		private $middleware_priority = [];


		public function setMiddlewareGroups( array $middleware_groups ) {

			$this->middleware_groups = $middleware_groups;

		}

		public function setRouteMiddlewareAliases( array $route_middleware_aliases ) {

			$this->route_middleware_aliases = $route_middleware_aliases;

		}

		public function setMiddlewarePriority( array $middleware_priority ) {

			$this->middleware_priority = $middleware_priority;

		}

	}